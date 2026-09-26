<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('renders the login page with UMSS institutional design and return link', function () {
    $this->withoutVite();

    $response = $this->get('/login');

    $response->assertOk()
        ->assertSee('UMSS')
        ->assertSee('Acceso Institucional')
        ->assertSee('Ingrese sus credenciales')
        ->assertSee('Volver al catálogo de cursos')
        ->assertSee('Iniciar Sesión');
});

it('authenticates admin and redirects to admin panel', function () {
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);

    $admin = User::factory()->create([
        'email' => 'admin@umss.edu.bo',
        'password' => bcrypt('secret123'),
    ]);
    $admin->assignRole(UserRole::ADMIN->value);

    $response = $this->withoutMiddleware(ValidateCsrfToken::class)
        ->post('/login', [
            'email' => 'admin@umss.edu.bo',
            'password' => 'secret123',
        ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($admin);
});

it('authenticates instructor and redirects to instructor panel', function () {
    Role::firstOrCreate(['name' => UserRole::INSTRUCTOR->value]);

    $instructor = User::factory()->create([
        'email' => 'docente@umss.edu.bo',
        'password' => bcrypt('secret123'),
    ]);
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $response = $this->withoutMiddleware(ValidateCsrfToken::class)
        ->post('/login', [
            'email' => 'docente@umss.edu.bo',
            'password' => 'secret123',
        ]);

    $response->assertRedirect('/instructor');
    $this->assertAuthenticatedAs($instructor);
});

it('rejects invalid credentials', function () {
    $response = $this->withoutMiddleware(ValidateCsrfToken::class)
        ->post('/login', [
            'email' => 'fake@umss.edu.bo',
            'password' => 'wrongpass',
        ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('rate limits repeated login attempts', function () {
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post('/login', [
                'email' => 'fake@umss.edu.bo',
                'password' => 'wrongpass',
            ])
            ->assertStatus(302);
    }

    $this->withoutMiddleware(ValidateCsrfToken::class)
        ->post('/login', [
            'email' => 'fake@umss.edu.bo',
            'password' => 'wrongpass',
        ])
        ->assertStatus(429);
});
