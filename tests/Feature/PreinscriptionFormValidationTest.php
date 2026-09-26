<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Livewire\PreinscriptionComponent;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Services\PreinscriptionService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
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

it('rejects service registration when the group is not enabled', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::NO_HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    expect(fn () => app(PreinscriptionService::class)->register([
        'group_id' => $group->id,
        'ci' => '7894561',
        'nombres' => 'Fabio Santos',
        'apellido_paterno' => 'Fernandez',
        'email' => 'fabio.fernandez@umss.edu.bo',
        'tipo_participante' => 'umss',
    ]))->toThrow(ValidationException::class, 'no está habilitado');

    expect(Preinscription::where('ci', '7894561')->exists())->toBeFalse();
});

it('blocks re-registration when a rejected preinscription already exists for the same ci and group', function () {
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

    expect(fn () => app(PreinscriptionService::class)->register([
        'group_id' => $group->id,
        'ci' => '7894561',
        'nombres' => 'Fabio Santos',
        'apellido_paterno' => 'Fernandez',
        'email' => 'fabio.fernandez@umss.edu.bo',
        'tipo_participante' => 'umss',
    ]))->toThrow(ValidationException::class, 'rechazada');

    expect(Preinscription::where('ci', '7894561')->count())->toBe(1);
});

it('redirects to the catalog when mounting a group that is not enabled', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::NO_HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->assertRedirect(route('home'));

    expect(session()->has('error'))->toBeTrue();
});

it('redirects to the catalog when mounting a group whose course is not published', function () {
    $course = Course::factory()->create([
        'carga_horaria' => '20',
        'status' => CourseStatus::EN_PREPARACION,
    ]);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->assertRedirect(route('home'));
});

it('rejects selecting a group that belongs to a foreign course', function () {
    $courseA = Course::factory()->create(['carga_horaria' => '20']);
    $groupA = Group::factory()->create([
        'course_id' => $courseA->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $courseB = Course::factory()->create(['carga_horaria' => '30']);
    $groupB = Group::factory()->create([
        'course_id' => $courseB->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $groupA])
        ->set('groupId', $groupB->id)
        ->assertHasErrors(['groupId'])
        ->assertSet('groupId', $groupA->id);

    expect(Preinscription::where('group_id', $groupB->id)->exists())->toBeFalse();
});
