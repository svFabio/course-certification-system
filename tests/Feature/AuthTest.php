<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('renders the login page with UMSS institutional design and return link', function () {
    $response = $this->get('/login');

    $response->assertOk()
        ->assertSee('UMSS')
        ->assertSee('Acceso Institucional')
        ->assertSee('Ingrese sus credenciales')
        ->assertSee('Volver al catálogo de cursos')
        ->assertSee('Iniciar Sesión');
});

it('authenticates admin and redirects to admin panel', function () {
    Role::firstOrCreate(['name' => 'admin']);

    $admin = User::factory()->create([
        'email' => 'admin@umss.edu.bo',
        'password' => bcrypt('secret123'),
    ]);
    $admin->assignRole('admin');

    $response = $this->post('/login', [
        'email' => 'admin@umss.edu.bo',
        'password' => 'secret123',
    ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($admin);
});

it('authenticates instructor and redirects to instructor panel', function () {
    Role::firstOrCreate(['name' => 'instructor']);

    $instructor = User::factory()->create([
        'email' => 'docente@umss.edu.bo',
        'password' => bcrypt('secret123'),
    ]);
    $instructor->assignRole('instructor');

    $response = $this->post('/login', [
        'email' => 'docente@umss.edu.bo',
        'password' => 'secret123',
    ]);

    $response->assertRedirect('/instructor');
    $this->assertAuthenticatedAs($instructor);
});

it('rejects invalid credentials', function () {
    $response = $this->post('/login', [
        'email' => 'fake@umss.edu.bo',
        'password' => 'wrongpass',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});
