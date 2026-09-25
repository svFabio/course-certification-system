<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('redirects a student to the student panel after login', function () {
    $user = User::factory()->create([
        'email' => 'student@umss.edu.bo',
        'password' => Hash::make('password'),
    ]);
    $user->assignRole(UserRole::STUDENT->value);

    $this->post('/login', [
        'email' => 'student@umss.edu.bo',
        'password' => 'password',
    ])->assertRedirect('/estudiante');

    $this->assertAuthenticatedAs($user);
});

it('allows a student to open the student panel', function () {
    $user = User::factory()->create([
        'email' => 'student@umss.edu.bo',
        'password' => Hash::make('password'),
    ]);
    $user->assignRole(UserRole::STUDENT->value);

    $this->actingAs($user)
        ->get('/estudiante')
        ->assertOk();
});

it('forbids a student from opening the admin panel', function () {
    $user = User::factory()->create([
        'email' => 'student@umss.edu.bo',
        'password' => Hash::make('password'),
    ]);
    $user->assignRole(UserRole::STUDENT->value);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
