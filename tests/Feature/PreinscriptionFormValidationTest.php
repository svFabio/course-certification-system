<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GroupStatus;
use App\Livewire\PreinscriptionComponent;
use App\Models\Course;
use App\Models\Group;
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
