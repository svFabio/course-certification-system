<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Group;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('does not publish a course without a configured group', function () {
    $course = Course::factory()->draft()->create();

    expect(fn () => app(CourseService::class)->publish(
        $course,
        now()->toDateString(),
        now()->addDays(7)->toDateString(),
    ))->toThrow(ValidationException::class);
});

it('publishes a course with a group, schedule and capacity', function () {
    $course = Course::factory()->draft()->create();
    Group::factory()->create(['course_id' => $course->id]);

    $published = app(CourseService::class)->publish(
        $course,
        now()->toDateString(),
        now()->addDays(10)->toDateString(),
    );

    expect($published->status)->toBe(CourseStatus::PUBLICADO)
        ->and($published->fecha_inicio_preinscripcion->toDateString())->toBe(now()->toDateString())
        ->and($published->fecha_fin_preinscripcion->toDateString())->toBe(now()->addDays(10)->toDateString());
});

it('does not publish when the preinscription end date already passed', function () {
    $course = Course::factory()->draft()->create();
    Group::factory()->create(['course_id' => $course->id]);

    expect(fn () => app(CourseService::class)->publish(
        $course,
        now()->subDays(10)->toDateString(),
        now()->subDay()->toDateString(),
    ))->toThrow(ValidationException::class);
});

it('closes preinscription and stops accepting new registrations', function () {
    $course = Course::factory()->published()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    $closed = app(CourseService::class)->closePreinscription($course);

    expect($closed->status)->toBe(CourseStatus::PREINSCRIPCION_CERRADA)
        ->and(app(CourseService::class)->isPreinscriptionOpen($closed))->toBeFalse();

    expect(fn () => app(\App\Services\PreinscriptionService::class)->register([
        'group_id' => $group->id,
        'ci' => '1234567',
        'nombres' => 'Ana',
        'apellido_paterno' => 'Perez',
        'email' => 'ana@example.com',
        'tipo_participante' => 'umss',
    ]))->toThrow(ValidationException::class);
});

it('automatically closes preinscription after the deadline', function () {
    $course = Course::factory()->published()->create([
        'fecha_inicio_preinscripcion' => now()->subDays(10)->toDateString(),
        'fecha_fin_preinscripcion' => now()->subDay()->toDateString(),
    ]);

    $closed = app(CourseService::class)->closeExpiredPreinscriptions();

    expect($closed)->toBe(1)
        ->and($course->fresh()->status)->toBe(CourseStatus::PREINSCRIPCION_CERRADA);
});

it('generates a shareable course summary with name, schedule, cost and capacity', function () {
    $instructor = User::factory()->create(['name' => 'Juan Docente']);
    $course = Course::factory()->published()->create([
        'nombre' => 'Laravel Avanzado',
        'instructor_id' => $instructor->id,
        'precio_umss' => 80,
    ]);
    Group::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Grupo A',
        'hora_inicio' => '08:00',
        'hora_fin' => '09:30',
        'cupo_maximo' => 30,
    ]);

    $summary = app(CourseService::class)->publicationSummary($course);

    expect($summary)
        ->toContain('Laravel Avanzado')
        ->toContain('Grupo A')
        ->toContain('08:00')
        ->toContain('Bs. 80.00')
        ->toContain('Cupos: 30');
});
