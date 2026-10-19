<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GroupStatus;
use App\Livewire\PreinscriptionComponent;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('accepts a preinscription whose email domain does not resolve in DNS', function () {
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

    // `.invalid` is reserved by RFC 6761 and never resolves in DNS.
    $email = 'estudiante@dominio-ficticio-umss.invalid';

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', $ci)
        ->set('nombres', fake()->firstName())
        ->set('apellidoPaterno', fake()->lastName())
        ->set('apellidoMaterno', fake()->lastName())
        ->set('celular', '7'.fake()->numerify('#######'))
        ->set('email', $email)
        ->set('tipoParticipante', 'umss')
        ->set('codSis', (string) fake()->numerify('#########'))
        ->call('goToConfirmation')
        ->assertHasNoErrors(['email'])
        ->call('submit')
        ->assertSet('isSubmitted', true);

    expect(Preinscription::where('ci', $ci)->where('email', $email)->exists())->toBeTrue();
});

it('still rejects a syntactically invalid email address', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Livewire::test(PreinscriptionComponent::class, ['group' => $group])
        ->set('ci', (string) fake()->numerify('#######'))
        ->set('nombres', fake()->firstName())
        ->set('apellidoPaterno', fake()->lastName())
        ->set('apellidoMaterno', fake()->lastName())
        ->set('celular', '7'.fake()->numerify('#######'))
        ->set('email', 'correo-invalido')
        ->set('tipoParticipante', 'umss')
        ->set('codSis', (string) fake()->numerify('#########'))
        ->call('goToConfirmation')
        ->assertHasErrors(['email'])
        ->assertSet('stepConfirmation', false);
});
