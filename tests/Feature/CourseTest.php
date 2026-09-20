<?php

declare(strict_types=1);

use App\Enums\CertificateType;
use App\Models\Course;
use App\Models\EvaluationCriteria;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\EvaluationService;
use App\Services\PreinscriptionService;
use App\Support\BusinessRules;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

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
    expect(BusinessRules::determineCertificateType(70))->toBe(CertificateType::APROBACION);
    expect(BusinessRules::determineCertificateType(69.99))->toBe(CertificateType::ASISTENCIA);
    expect(BusinessRules::determineCertificateType(100))->toBe(CertificateType::APROBACION);
    expect(BusinessRules::determineCertificateType(0))->toBe(CertificateType::ASISTENCIA);
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

it('rejects preinscription when group capacity is full', function () {
    $group = Group::factory()->create([
        'cupo_minimo' => 15,
        'cupo_maximo' => 2,
    ]);

    $service = app(PreinscriptionService::class);

    $service->register([
        'group_id' => $group->id,
        'ci' => '1111111',
        'nombres' => 'Juan',
        'apellido_paterno' => 'Perez',
        'email' => 'juan@example.com',
        'tipo_participante' => 'umss',
    ]);

    $service->register([
        'group_id' => $group->id,
        'ci' => '2222222',
        'nombres' => 'Maria',
        'apellido_paterno' => 'Gomez',
        'email' => 'maria@example.com',
        'tipo_participante' => 'externo',
    ]);

    expect(fn () => $service->register([
        'group_id' => $group->id,
        'ci' => '3333333',
        'nombres' => 'Carlos',
        'apellido_paterno' => 'Lopez',
        'email' => 'carlos@example.com',
        'tipo_participante' => 'auxiliar',
    ]))->toThrow(ValidationException::class);
});

it('calculates weighted final grade correctly via EvaluationService', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    $service = app(EvaluationService::class);
    $service->setCriteria($course, [
        ['nombre' => 'Examen Parcial', 'ponderacion' => 40.0],
        ['nombre' => 'Proyecto Final', 'ponderacion' => 60.0],
    ]);

    $criteria = $course->evaluationCriteria()->get();
    $service->recordGrades($preinscription, [
        $criteria[0]->id => 80.0,
        $criteria[1]->id => 90.0,
    ]);

    $finalGrade = $service->calculateFinalGrade($preinscription);
    // (80 * 0.4) + (90 * 0.6) = 32 + 54 = 86.0
    expect($finalGrade)->toBe(86.0);
});

it('generates certificate with correct type and matching course_id via CertificateService', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    $evalService = app(EvaluationService::class);
    $evalService->setCriteria($course, [
        ['nombre' => 'Nota General', 'ponderacion' => 100.0],
    ]);

    $criteria = $course->evaluationCriteria()->first();
    $evalService->recordGrades($preinscription, [$criteria->id => 75.0]);

    $certService = app(CertificateService::class);
    $certificate = $certService->generate($preinscription);

    expect($certificate->course_id)->toBe($course->id);
    expect($certificate->tipo)->toBe(CertificateType::APROBACION);
    expect($certificate->codigo_unico)->toStartWith('CERT-');
});
