<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\EvaluationCriteria;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use App\Support\BusinessRules;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a course with correct automatic pricing for 20h', function () {
    $instructor = User::factory()->create();

    $course = Course::create([
        'nombre' => 'Curso de Laravel',
        'carga_horaria' => '20',
        'nivel' => 'Básico',
        'periodo' => '2024-II',
        'precio_umss' => BusinessRules::calculatePrice(20, 'umss'),
        'precio_externo' => BusinessRules::calculatePrice(20, 'externo'),
        'precio_auxiliar' => BusinessRules::calculatePrice(20, 'auxiliar'),
        'instructor_id' => $instructor->id,
    ]);

    expect($course)->not->toBeNull();
    expect((float) $course->precio_umss)->toBe(80.00);
    expect((float) $course->precio_externo)->toBe(100.00);
    expect((float) $course->precio_auxiliar)->toBe(40.00);
});

it('creates a course with factory for 30h with matching business rules', function () {
    $course = Course::factory()->withHours('30')->create();

    expect((float) $course->precio_umss)->toBe(120.00);
    expect((float) $course->precio_externo)->toBe(150.00);
    expect((float) $course->precio_auxiliar)->toBe(60.00);
});

it('calculates correct pricing via BusinessRules', function () {
    expect(BusinessRules::calculatePrice(20, 'umss'))->toBe(80.00);
    expect(BusinessRules::calculatePrice(20, 'externo'))->toBe(100.00);
    expect(BusinessRules::calculatePrice(20, 'auxiliar'))->toBe(40.00);
    expect(BusinessRules::calculatePrice(30, 'umss'))->toBe(120.00);
    expect(BusinessRules::calculatePrice(30, 'externo'))->toBe(150.00);
    expect(BusinessRules::calculatePrice(30, 'auxiliar'))->toBe(60.00);
});

it('determines certificate type based on grade', function () {
    expect(BusinessRules::determineCertificateType(70))->toBe('aprobacion');
    expect(BusinessRules::determineCertificateType(69.99))->toBe('asistencia');
    expect(BusinessRules::determineCertificateType(100))->toBe('aprobacion');
    expect(BusinessRules::determineCertificateType(0))->toBe('asistencia');
});

it('calculates haversine distance correctly', function () {
    $lat1 = -17.7833;
    $lng1 = -66.1500;
    $lat2 = -17.7843;
    $lng2 = -66.1510;

    $distance = BusinessRules::haversineDistance($lat1, $lng1, $lat2, $lng2);
    expect($distance)->toBeGreaterThan(50);
    expect($distance)->toBeLessThan(200);
});

it('validates minimum and maximum group capacity', function () {
    $group = Group::factory()->create([
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    expect($group->cupo_minimo)->toBe(15);
    expect($group->cupo_maximo)->toBe(30);
    expect($group->capacity_percentage)->toBe(0.0);

    Preinscription::factory()->count(6)->inscrito()->create([
        'group_id' => $group->id,
    ]);

    expect($group->fresh()->capacity_percentage)->toBe(20.0);
});

it('validates evaluation criteria total equals 100', function () {
    $course = Course::factory()->create();

    EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'ponderacion' => 40.00,
    ]);
    EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'ponderacion' => 60.00,
    ]);

    $totalWeight = $course->evaluationCriteria()->sum('ponderacion');
    expect((float) $totalWeight)->toBe(100.00);
});

it('enforces unique ci per group in preinscriptions', function () {
    $group = Group::factory()->create();

    Preinscription::factory()->create([
        'group_id' => $group->id,
        'ci' => '12345678',
    ]);

    expect(fn () => Preinscription::factory()->create([
        'group_id' => $group->id,
        'ci' => '12345678',
    ]))->toThrow(QueryException::class);
});

it('allows same ci in different groups', function () {
    $group1 = Group::factory()->create();
    $group2 = Group::factory()->create();

    $p1 = Preinscription::factory()->create([
        'group_id' => $group1->id,
        'ci' => '87654321',
    ]);
    $p2 = Preinscription::factory()->create([
        'group_id' => $group2->id,
        'ci' => '87654321',
    ]);

    expect($p1->exists)->toBeTrue();
    expect($p2->exists)->toBeTrue();
});
