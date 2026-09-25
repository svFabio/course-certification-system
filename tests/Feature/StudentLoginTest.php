<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\LoginService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeRoleUser(string $role, string $email = 'user@umss.edu.bo'): User
{
    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make('password'),
    ]);
    $user->assignRole($role);

    return $user;
}

it('redirects a student to the student panel after login', function () {
    makeRoleUser('student', 'student@umss.edu.bo');

    $this->post('/login', [
        'email' => 'student@umss.edu.bo',
        'password' => 'password',
    ]);

    $this->get('/login')->assertOk();

    Livewire\Livewire::test(\App\Livewire\LoginComponent::class)
        ->set('email', 'student@umss.edu.bo')
        ->set('password', 'password')
        ->call('submit')
        ->assertRedirect('/estudiante');
});

it('redirects an admin to the admin panel after login', function () {
    makeRoleUser('admin', 'admin@umss.edu.bo');

    Livewire\Livewire::test(\App\Livewire\LoginComponent::class)
        ->set('email', 'admin@umss.edu.bo')
        ->set('password', 'password')
        ->call('submit')
        ->assertRedirect('/admin');
});

it('redirects an instructor to the instructor panel after login', function () {
    makeRoleUser('instructor', 'instructor@umss.edu.bo');

    Livewire\Livewire::test(\App\Livewire\LoginComponent::class)
        ->set('email', 'instructor@umss.edu.bo')
        ->set('password', 'password')
        ->call('submit')
        ->assertRedirect('/instructor');
});

it('shows correo o contraseña incorrectos when credentials fail', function () {
    makeRoleUser('student', 'student@umss.edu.bo');

    Livewire\Livewire::test(\App\Livewire\LoginComponent::class)
        ->set('email', 'student@umss.edu.bo')
        ->set('password', 'wrong')
        ->call('submit')
        ->assertHasErrors(['email']);
});

it('locks the account after five failed attempts', function () {
    makeRoleUser('student', 'student@umss.edu.bo');
    $service = app(LoginService::class);

    for ($i = 0; $i < 5; $i++) {
        try {
            $service->attempt('student@umss.edu.bo', 'wrong');
        } catch (ValidationException) {
            // expected
        }
    }

    expect(fn () => $service->attempt('student@umss.edu.bo', 'wrong'))
        ->toThrow(ValidationException::class);

    $key = $service->throttleKey('student@umss.edu.bo');
    expect(RateLimiter::tooManyAttempts($key, LoginService::MAX_ATTEMPTS))->toBeTrue();
});

it('forbids a student from opening the admin panel', function () {
    $student = makeRoleUser('student', 'student@umss.edu.bo');

    $this->actingAs($student)
        ->get('/admin')
        ->assertForbidden();
});
