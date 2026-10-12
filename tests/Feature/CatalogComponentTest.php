<?php

declare(strict_types=1);

use App\Enums\PreinscriptionStatus;
use App\Livewire\CatalogComponent;
use App\Models\Course;
use App\Models\CourseInterest;
use App\Models\Group;
use App\Models\Preinscription;
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

it('shows the interest call to action only for courses without available capacity', function () {
    $fullCourse = Course::factory()->create(['nombre' => 'Curso Lleno CTA']);
    $fullGroup = Group::factory()->habilitado()->create([
        'course_id' => $fullCourse->id,
        'cupo_maximo' => 1,
    ]);
    Preinscription::factory()->create([
        'group_id' => $fullGroup->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    $openCourse = Course::factory()->create(['nombre' => 'Curso Abierto CTA']);
    Group::factory()->habilitado()->create(['course_id' => $openCourse->id]);

    $html = Livewire::test(CatalogComponent::class)->html();

    expect(substr_count($html, 'Avisarme cuando haya cupo'))->toBe(1);
    expect($html)
        ->toContain('Curso Lleno CTA')
        ->toContain('Curso Abierto CTA');
});

it('does not offer interest registration when a free seat exists anywhere', function () {
    $course = Course::factory()->create();
    Group::factory()->habilitado()->create(['course_id' => $course->id, 'cupo_maximo' => 30]);

    $html = Livewire::test(CatalogComponent::class)->html();

    expect($html)
        ->not->toContain('Avisarme cuando haya cupo')
        ->toContain('Preinscribirse');
});

it('registers an anonymous interest from the catalog and shows a confirmation', function () {
    $course = Course::factory()->create(['nombre' => 'Curso Para Interesarse']);
    $fullGroup = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_maximo' => 1,
    ]);
    Preinscription::factory()->create([
        'group_id' => $fullGroup->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    $email = fake()->unique()->safeEmail();

    Livewire::test(CatalogComponent::class)
        ->call('openInterest', $course->id)
        ->set('interestEmail', $email)
        ->call('registerInterest')
        ->assertHasNoErrors()
        ->assertSee('¡Listo! Te avisaremos por correo');

    expect(CourseInterest::where('course_id', $course->id)->where('email', $email)->count())->toBe(1);
});

it('shows a friendly already-registered state when the same email registers twice', function () {
    $course = Course::factory()->create(['nombre' => 'Curso Duplicado Interes']);
    $fullGroup = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_maximo' => 1,
    ]);
    Preinscription::factory()->create([
        'group_id' => $fullGroup->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    $email = fake()->unique()->safeEmail();

    Livewire::test(CatalogComponent::class)
        ->call('openInterest', $course->id)
        ->set('interestEmail', $email)
        ->call('registerInterest')
        ->assertHasNoErrors()
        ->call('openInterest', $course->id)
        ->set('interestEmail', $email)
        ->call('registerInterest')
        ->assertHasNoErrors()
        ->assertSee('Ya estás anotado(a) para este curso');

    expect(CourseInterest::where('course_id', $course->id)->count())->toBe(1);
});
