<?php

declare(strict_types=1);

use App\Livewire\CatalogComponent;
use App\Models\Course;
use App\Models\Group;
use Livewire\Livewire;

it('renders a single preinscription trigger and keeps prices inside the modal', function () {
    $course = Course::factory()->create([
        'nombre' => 'Curso de Prueba Modal',
        'carga_horaria' => '30',
        'precio_umss' => 300,
        'precio_externo' => 350,
        'precio_auxiliar' => 150,
    ]);

    Group::factory()->habilitado()->create(['course_id' => $course->id, 'nombre' => 'Grupo Alfa']);
    Group::factory()->habilitado()->create(['course_id' => $course->id, 'nombre' => 'Grupo Beta']);

    $html = Livewire::test(CatalogComponent::class)->html();

    expect(substr_count($html, 'aria-haspopup="dialog"'))->toBe(1)
        ->and(substr_count($html, 'open = true'))->toBe(1)
        ->and(substr_count($html, 'Bs. 300.00'))->toBe(1)
        ->and(substr_count($html, 'Bs. 350.00'))->toBe(1)
        ->and(substr_count($html, 'Bs. 150.00'))->toBe(1)
        ->and(substr_count($html, 'Bs. 120.00'))->toBe(0);

    $modalStart = strpos($html, 'x-show="open"');

    expect($modalStart)->not->toBeFalse()
        ->and(strpos($html, 'Bs. 300.00'))->toBeGreaterThan($modalStart);

    expect($html)
        ->toContain($course->nombre)
        ->toContain('Carga horaria');
});

it('lists prices and enabled groups with their preinscription links inside the modal', function () {
    $course = Course::factory()->create([
        'nombre' => 'Curso con Grupos Modal',
        'carga_horaria' => '30',
        'precio_umss' => 300,
        'precio_externo' => 350,
        'precio_auxiliar' => 150,
    ]);

    $firstGroup = Group::factory()->habilitado()->create(['course_id' => $course->id, 'nombre' => 'Grupo Alfa']);
    $secondGroup = Group::factory()->habilitado()->create(['course_id' => $course->id, 'nombre' => 'Grupo Beta']);

    $component = Livewire::test(CatalogComponent::class);

    $component
        ->assertSee(['Precios', 'Grupos disponibles'])
        ->assertSee(['Bs. 300.00', 'Bs. 350.00', 'Bs. 150.00'])
        ->assertDontSee('Bs. 120.00')
        ->assertSee(['Grupo Alfa', 'Grupo Beta'])
        ->assertSee($firstGroup->hora_inicio->format('H:i').' - '.$firstGroup->hora_fin->format('H:i'));

    $firstLink = 'href="'.route('preinscripcion', ['group' => $firstGroup->id]).'"';
    $secondLink = 'href="'.route('preinscripcion', ['group' => $secondGroup->id]).'"';

    $component->assertSeeHtml($firstLink)->assertSeeHtml($secondLink);

    $html = $component->html();

    expect(substr_count($html, $firstLink))->toBe(1)
        ->and(substr_count($html, $secondLink))->toBe(1)
        ->and($firstGroup->nombre)->toBe('Grupo Alfa')
        ->and($secondGroup->nombre)->toBe('Grupo Beta');
});

it('wires the modal with local alpine state and accessible dialog markup', function () {
    $course = Course::factory()->create(['nombre' => 'Curso Accesible Modal']);

    Group::factory()->habilitado()->create(['course_id' => $course->id, 'nombre' => 'Grupo Alfa']);

    $component = Livewire::test(CatalogComponent::class);

    $component
        ->assertSeeHtml('x-data="{ open: false }"')
        ->assertSeeHtml('@click="open = true"')
        ->assertSeeHtml('@click="open = false"')
        ->assertSeeHtml('@keydown.escape.window="open = false"')
        ->assertSeeHtml('x-cloak')
        ->assertSeeHtml('role="dialog"')
        ->assertSeeHtml('aria-modal="true"')
        ->assertSeeHtml('aria-label="Cerrar"');
});
