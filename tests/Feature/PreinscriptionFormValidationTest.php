<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Livewire\PreinscriptionComponent;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('validates strictly against invalid inputs in preinscription form', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', 'CI-CON-LETRAS')
        ->set('nombres', 'Juan1234')
        ->set('apellidoPaterno', 'Perez$$$')
        ->set('celular', '75275375f')
        ->set('email', 'correo-invalido')
        ->set('tipoParticipante', 'categoria_inexistente')
        ->call('goToConfirmation')
        ->assertHasErrors(['ci', 'nombres', 'apellidoPaterno', 'celular', 'email', 'tipoParticipante'])
        ->assertSet('stepConfirmation', false);
});

it('accepts valid bolivian student data and proceeds to confirmation step', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', '7894561')
        ->set('nombres', 'Fabio Santos')
        ->set('apellidoPaterno', 'Fernandez')
        ->set('apellidoMaterno', 'Vargas')
        ->set('celular', '71234567')
        ->set('email', 'fabio.fernandez@umss.edu.bo')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', '202002515')
        ->call('goToConfirmation')
        ->assertHasNoErrors()
        ->assertSet('stepConfirmation', true);
});

it('displays dedicated success screen after preinscription submission instead of resetting form', function () {
    RateLimiter::clear('preinscripcion:ci:7894561');
    RateLimiter::clear('preinscripcion:ip:127.0.0.1');

    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', '7894561')
        ->set('nombres', 'Fabio Santos')
        ->set('apellidoPaterno', 'Fernandez')
        ->set('apellidoMaterno', 'Vargas')
        ->set('celular', '71234567')
        ->set('email', 'fabio.fernandez@umss.edu.bo')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', '202002515')
        ->call('goToConfirmation')
        ->call('submit')
        ->assertSet('isSubmitted', true)
        ->assertSee('¡Preinscripción Registrada Exitosamente!')
        ->assertSee('Instrucciones para completar su pago:')
        ->assertSee('Volver al Catálogo de Cursos');
});

it('blocks same-course preinscription when one is pending payment', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Preinscription::factory()->create([
        'ci' => '7894561',
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', '7894561')
        ->set('nombres', 'Fabio Santos')
        ->set('apellidoPaterno', 'Fernandez')
        ->set('apellidoMaterno', 'Vargas')
        ->set('celular', '71234567')
        ->set('email', 'fabio.fernandez@umss.edu.bo')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', '202002515')
        ->call('goToConfirmation')
        ->assertHasErrors(['ci'])
        ->assertSee('Ya enviaste tu preinscripción para este curso');
});

it('blocks same-course preinscription when already enrolled', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Preinscription::factory()->create([
        'ci' => '7894561',
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', '7894561')
        ->set('nombres', 'Fabio Santos')
        ->set('apellidoPaterno', 'Fernandez')
        ->set('apellidoMaterno', 'Vargas')
        ->set('celular', '71234567')
        ->set('email', 'fabio.fernandez@umss.edu.bo')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', '202002515')
        ->call('goToConfirmation')
        ->assertHasErrors(['ci'])
        ->assertSee('Ya estás inscrito(a) en este curso');
});

it('allows retry after rejected preinscription', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Preinscription::factory()->create([
        'ci' => '7894561',
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::RECHAZADO,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', '7894561')
        ->set('nombres', 'Fabio Santos')
        ->set('apellidoPaterno', 'Fernandez')
        ->set('apellidoMaterno', 'Vargas')
        ->set('celular', '71234567')
        ->set('email', 'fabio.fernandez@umss.edu.bo')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', '202002515')
        ->call('goToConfirmation')
        ->assertHasNoErrors()
        ->assertSet('stepConfirmation', true);
});

it('allows preinscription in a different course', function () {
    $courseA = Course::factory()->create(['carga_horaria' => '20']);
    $groupA = Group::factory()->create([
        'course_id' => $courseA->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Preinscription::factory()->create([
        'ci' => '7894561',
        'group_id' => $groupA->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $courseB = Course::factory()->create(['carga_horaria' => '20']);
    $groupB = Group::factory()->create([
        'course_id' => $courseB->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $groupB])
        ->set('ci', '7894561')
        ->set('nombres', 'Fabio Santos')
        ->set('apellidoPaterno', 'Fernandez')
        ->set('apellidoMaterno', 'Vargas')
        ->set('celular', '71234567')
        ->set('email', 'fabio.fernandez@umss.edu.bo')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', '202002515')
        ->call('goToConfirmation')
        ->assertHasNoErrors()
        ->assertSet('stepConfirmation', true);
});

it('rate limits repeated submissions for the same ci', function () {
    RateLimiter::clear('preinscripcion:ci:7894561');
    RateLimiter::clear('preinscripcion:ip:127.0.0.1');

    RateLimiter::hit('preinscripcion:ci:7894561', 3600);
    RateLimiter::hit('preinscripcion:ci:7894561', 3600);
    RateLimiter::hit('preinscripcion:ci:7894561', 3600);

    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', '7894561')
        ->set('nombres', 'Fabio Santos')
        ->set('apellidoPaterno', 'Fernandez')
        ->set('apellidoMaterno', 'Vargas')
        ->set('celular', '71234567')
        ->set('email', 'fabio.fernandez@umss.edu.bo')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', '202002515')
        ->call('submit')
        ->assertHasErrors(['ci'])
        ->assertSee('demasiados intentos');
});
