<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Group;

it('renders open graph meta tags on the preinscription page', function () {
    $this->withoutVite();

    $course = Course::factory()->create([
        'nombre' => 'Curso de Especialización Laravel',
        'contenido' => 'Aprende arquitectura limpia y TDD.',
        'portada_path' => 'https://images.unsplash.com/photo-laravel?auto=format&w=800',
    ]);

    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
    ]);

    $response = $this->get(route('preinscripcion', $group));

    $response->assertOk()
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('Curso: Curso de Especialización Laravel', false)
        ->assertSee('https://images.unsplash.com/photo-laravel', false)
        ->assertSee('Formación Continua UMSS', false)
        ->assertSee('twitter:card', false);
});
