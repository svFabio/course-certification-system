<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Enums\TipoParticipante;
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
        ->set('nombres', fake()->firstName().'1234')
        ->set('apellidoPaterno', fake()->lastName().'$$$')
        ->set('celular', '7'.fake()->numerify('######').'f')
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
        ->set('ci', (string) fake()->numerify('#######'))
        ->set('nombres', fake()->firstName().' '.fake()->firstName())
        ->set('apellidoPaterno', fake()->lastName())
        ->set('apellidoMaterno', fake()->lastName())
        ->set('celular', '7'.fake()->numerify('#######'))
        ->set('email', fake()->userName().'@gmail.com')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', (string) fake()->numerify('#########'))
        ->call('goToConfirmation')
        ->assertHasNoErrors()
        ->assertSet('stepConfirmation', true);
});

it('displays dedicated success screen after preinscription submission instead of resetting form', function () {
    $ci = (string) fake()->numerify('#######');
    RateLimiter::clear("preinscripcion:ci:{$ci}");
    RateLimiter::clear('preinscripcion:ip:127.0.0.1');

    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', $ci)
        ->set('nombres', fake()->firstName())
        ->set('apellidoPaterno', fake()->lastName())
        ->set('apellidoMaterno', fake()->lastName())
        ->set('celular', '7'.fake()->numerify('#######'))
        ->set('email', fake()->userName().'@gmail.com')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', (string) fake()->numerify('#########'))
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

    $ci = (string) fake()->numerify('#######');
    Preinscription::factory()->create([
        'ci' => $ci,
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', $ci)
        ->set('nombres', fake()->firstName())
        ->set('apellidoPaterno', fake()->lastName())
        ->set('apellidoMaterno', fake()->lastName())
        ->set('celular', '7'.fake()->numerify('#######'))
        ->set('email', fake()->userName().'@gmail.com')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', (string) fake()->numerify('#########'))
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

    $ci = (string) fake()->numerify('#######');
    Preinscription::factory()->create([
        'ci' => $ci,
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', $ci)
        ->set('nombres', fake()->firstName())
        ->set('apellidoPaterno', fake()->lastName())
        ->set('apellidoMaterno', fake()->lastName())
        ->set('celular', '7'.fake()->numerify('#######'))
        ->set('email', fake()->userName().'@gmail.com')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', (string) fake()->numerify('#########'))
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

    $ci = (string) fake()->numerify('#######');
    Preinscription::factory()->create([
        'ci' => $ci,
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::RECHAZADO,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', $ci)
        ->set('nombres', fake()->firstName())
        ->set('apellidoPaterno', fake()->lastName())
        ->set('apellidoMaterno', fake()->lastName())
        ->set('celular', '7'.fake()->numerify('#######'))
        ->set('email', fake()->userName().'@gmail.com')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', (string) fake()->numerify('#########'))
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

    $ci = (string) fake()->numerify('#######');
    Preinscription::factory()->create([
        'ci' => $ci,
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
        ->set('ci', $ci)
        ->set('nombres', fake()->firstName())
        ->set('apellidoPaterno', fake()->lastName())
        ->set('apellidoMaterno', fake()->lastName())
        ->set('celular', '7'.fake()->numerify('#######'))
        ->set('email', fake()->userName().'@gmail.com')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', (string) fake()->numerify('#########'))
        ->call('goToConfirmation')
        ->assertHasNoErrors()
        ->assertSet('stepConfirmation', true);
});

it('rate limits repeated submissions for the same ci', function () {
    $ci = (string) fake()->numerify('#######');
    RateLimiter::clear("preinscripcion:ci:{$ci}");
    RateLimiter::clear('preinscripcion:ip:127.0.0.1');

    RateLimiter::hit("preinscripcion:ci:{$ci}", 3600);
    RateLimiter::hit("preinscripcion:ci:{$ci}", 3600);
    RateLimiter::hit("preinscripcion:ci:{$ci}", 3600);

    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', $ci)
        ->set('nombres', fake()->firstName())
        ->set('apellidoPaterno', fake()->lastName())
        ->set('apellidoMaterno', fake()->lastName())
        ->set('celular', '7'.fake()->numerify('#######'))
        ->set('email', fake()->userName().'@gmail.com')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', (string) fake()->numerify('#########'))
        ->call('submit')
        ->assertHasErrors(['ci'])
        ->assertSee('demasiados intentos');
});

it('allows service registration when the group is in no_habilitado status', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::NO_HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $ci = (string) fake()->numerify('#######');
    $preinscription = app(PreinscriptionService::class)->register([
        'group_id' => $group->id,
        'ci' => $ci,
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
        'email' => fake()->safeEmail(),
        'tipo_participante' => TipoParticipante::UMSS,
    ]);

    expect($preinscription)->toBeInstanceOf(Preinscription::class);
    expect(Preinscription::where('ci', $ci)->exists())->toBeTrue();
});

it('rejects service registration when the group is closed', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::CERRADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $ci = (string) fake()->numerify('#######');
    expect(fn () => app(PreinscriptionService::class)->register([
        'group_id' => $group->id,
        'ci' => $ci,
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
        'email' => fake()->safeEmail(),
        'tipo_participante' => TipoParticipante::UMSS,
    ]))->toThrow(ValidationException::class, 'no está habilitado');

    expect(Preinscription::where('ci', $ci)->exists())->toBeFalse();
});

it('blocks re-registration when a rejected preinscription already exists for the same ci and group', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $ci = (string) fake()->numerify('#######');
    Preinscription::factory()->create([
        'ci' => $ci,
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::RECHAZADO,
    ]);

    expect(fn () => app(PreinscriptionService::class)->register([
        'group_id' => $group->id,
        'ci' => $ci,
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
        'email' => fake()->safeEmail(),
        'tipo_participante' => TipoParticipante::UMSS,
    ]))->toThrow(ValidationException::class, 'rechazada');

    expect(Preinscription::where('ci', $ci)->count())->toBe(1);
});

it('allows mounting a group in no_habilitado status in the preinscription component', function () {
    $course = Course::factory()->create([
        'carga_horaria' => '20',
        'status' => CourseStatus::PUBLICADO,
    ]);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::NO_HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->assertOk()
        ->assertSet('groupId', $group->id);
});

it('redirects to the catalog when mounting a group that is closed', function () {
    $course = Course::factory()->create([
        'carga_horaria' => '20',
        'status' => CourseStatus::PUBLICADO,
    ]);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::CERRADO,
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

it('maps group_id validation error from service to groupId in livewire form', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 1,
    ]);

    // Fill capacity
    Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    $ci = (string) fake()->numerify('#######');

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', $ci)
        ->set('nombres', fake()->firstName())
        ->set('apellidoPaterno', fake()->lastName())
        ->set('apellidoMaterno', fake()->lastName())
        ->set('celular', '7'.fake()->numerify('#######'))
        ->set('email', fake()->userName().'@gmail.com')
        ->set('tipoParticipante', 'externo')
        ->set('stepConfirmation', true)
        ->call('submit')
        ->assertHasErrors(['groupId'])
        ->assertSet('stepConfirmation', false);
});

it('moves preinscription to another group within the same course inside a transaction', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group1 = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);
    $group2 = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $preinscription = Preinscription::factory()->create([
        'group_id' => $group1->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $service = app(PreinscriptionService::class);
    $moved = $service->moveToGroup($preinscription, $group2);

    expect($moved->group_id)->toBe($group2->id);
    expect($preinscription->fresh()->group_id)->toBe($group2->id);
});

it('rejects service registration when the group course has preinscription closed', function () {
    $course = Course::factory()->create([
        'carga_horaria' => '20',
        'status' => CourseStatus::PREINSCRIPCION_CERRADA,
    ]);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $ci = (string) fake()->numerify('#######');

    expect(fn () => app(PreinscriptionService::class)->register([
        'group_id' => $group->id,
        'ci' => $ci,
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
        'email' => fake()->safeEmail(),
        'tipo_participante' => TipoParticipante::UMSS,
    ]))->toThrow(ValidationException::class, 'no acepta preinscripciones');

    expect(Preinscription::where('ci', $ci)->exists())->toBeFalse();
});

it('allows service registration when the group course is published', function () {
    $course = Course::factory()->create([
        'carga_horaria' => '20',
        'status' => CourseStatus::PUBLICADO,
    ]);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $ci = (string) fake()->numerify('#######');

    $preinscription = app(PreinscriptionService::class)->register([
        'group_id' => $group->id,
        'ci' => $ci,
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
        'email' => fake()->safeEmail(),
        'tipo_participante' => TipoParticipante::UMSS,
    ]);

    expect($preinscription)->toBeInstanceOf(Preinscription::class);
    expect($preinscription->status)->toBe(PreinscriptionStatus::PENDIENTE_PAGO);
});

it('blocks duplicate preinscription in form when CI complement casing differs', function () {
    $course = Course::factory()->create([
        'carga_horaria' => '20',
        'status' => CourseStatus::PUBLICADO,
    ]);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $baseCiNumber = (string) fake()->numerify('#######');
    $upperCi = $baseCiNumber.'-1A';
    $lowerCi = $baseCiNumber.'-1a';

    Preinscription::factory()->create([
        'ci' => $upperCi,
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', $lowerCi)
        ->set('nombres', fake()->firstName())
        ->set('apellidoPaterno', fake()->lastName())
        ->set('apellidoMaterno', fake()->lastName())
        ->set('celular', '7'.fake()->numerify('#######'))
        ->set('email', fake()->userName().'@gmail.com')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', (string) fake()->numerify('#########'))
        ->call('goToConfirmation')
        ->assertHasErrors(['ci'])
        ->assertSee('Ya enviaste tu preinscripción para este curso');
});

it('blocks duplicate registration in service when CI complement casing differs and normalizes CI to uppercase', function () {
    $course = Course::factory()->create([
        'carga_horaria' => '20',
        'status' => CourseStatus::PUBLICADO,
    ]);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $baseCiNumber = (string) fake()->numerify('#######');
    $upperCi = $baseCiNumber.'-1A';
    $lowerCi = ' '.$baseCiNumber.'-1a ';

    $registered = app(PreinscriptionService::class)->register([
        'group_id' => $group->id,
        'ci' => $lowerCi,
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
        'email' => fake()->safeEmail(),
        'tipo_participante' => TipoParticipante::UMSS,
    ]);

    expect($registered->ci)->toBe($upperCi);
    expect($registered->getRawOriginal('ci'))->toBe($upperCi);

    expect(fn () => app(PreinscriptionService::class)->register([
        'group_id' => $group->id,
        'ci' => $upperCi,
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
        'email' => fake()->safeEmail(),
        'tipo_participante' => TipoParticipante::UMSS,
    ]))->toThrow(ValidationException::class, 'Ya enviaste tu preinscripción para este curso');
});

it('blocks moving preinscription when destination group already has the same CI in different casing', function () {
    $course = Course::factory()->create([
        'carga_horaria' => '20',
        'status' => CourseStatus::PUBLICADO,
    ]);
    $groupOrigin = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
    ]);
    $groupDest = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
    ]);

    $baseCiNumber = (string) fake()->numerify('#######');
    $upperCi = $baseCiNumber.'-1A';
    $lowerCi = $baseCiNumber.'-1a';

    $preinscriptionDest = Preinscription::factory()->create([
        'ci' => $upperCi,
        'group_id' => $groupDest->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $preinscriptionOrigin = Preinscription::factory()->create([
        'ci' => $lowerCi,
        'group_id' => $groupOrigin->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    expect(fn () => app(PreinscriptionService::class)->moveToGroup($preinscriptionOrigin, $groupDest))
        ->toThrow(ValidationException::class, 'El participante ya está preinscrito en el grupo de destino.');
});
