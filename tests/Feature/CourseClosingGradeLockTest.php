<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Filament\Instructor\Pages\GradeMatrix;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use App\Services\CourseService;
use App\Services\EvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::INSTRUCTOR->value]);
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
});

it('blocks altering grades and criteria once a course is finalized', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);
    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
        'status' => CourseStatus::EN_CURSO,
        'attendance_weight' => 20,
    ]);
    $group = Group::factory()->habilitado()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $eval = app(EvaluationService::class);
    $criterion = $eval->addCriteria($course, 'Parcial', 80);
    $eval->recordGrades($preinscription, [$criterion->id => 85.0]);

    // Close the course
    app(CourseService::class)->close($course->fresh());
    expect($course->fresh()->status)->toBe(CourseStatus::FINALIZADO);

    // 1. Service blocks modifying grades
    expect(fn () => $eval->recordGrades($preinscription->fresh(), [$criterion->id => 99.0]))
        ->toThrow(ValidationException::class);

    // 2. Service blocks applyEvaluationConfig
    expect(fn () => $eval->applyEvaluationConfig($course->fresh(), 20, [
        ['id' => $criterion->id, 'nombre' => 'Parcial Modificado', 'ponderacion' => 80, 'op' => 'update'],
    ], []))->toThrow(ValidationException::class);

    // 3. Service blocks adding or deleting criteria
    expect(fn () => $eval->addCriteria($course->fresh(), 'Nuevo', 10))
        ->toThrow(ValidationException::class);
    expect(fn () => $eval->removeCriteria($criterion->fresh()))
        ->toThrow(ValidationException::class);

    // 4. Policy blocks update and delete
    $grade = Grade::where('preinscription_id', $preinscription->id)->firstOrFail();
    expect($instructor->can('update', $grade->fresh()))->toBeFalse()
        ->and($instructor->can('delete', $grade->fresh()))->toBeFalse();

    // 5. GradeMatrix displays locked banner and blocks save
    $this->actingAs($instructor);
    Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->assertSee('Curso finalizado — Calificaciones y evaluaciones bloqueadas')
        ->call('saveAll')
        ->assertSee('El curso se encuentra finalizado y sus calificaciones están bloqueadas.');
});
