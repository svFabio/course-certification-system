<?php

declare(strict_types=1);

use App\Enums\CertificateType;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\EvaluationCriteria;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use App\Services\CertificateService;
use App\Services\EvaluationService;
use App\Services\SheetExportBuilder;
use App\Support\BusinessRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates a course with default attendance_weight of 50', function () {
    $course = Course::factory()->create();

    expect($course->fresh()->attendance_weight)->toBe(BusinessRules::DEFAULT_ATTENDANCE_WEIGHT);
});

it('sets a valid attendance weight when the combined sum is 100', function () {
    $course = Course::factory()->create();
    EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 20.00]);
    EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 30.00]);

    app(EvaluationService::class)->setAttendanceWeight($course, 50);

    expect($course->fresh()->attendance_weight)->toBe(50);
});

it('rejects an attendance weight whose combined sum is not 100', function () {
    $course = Course::factory()->create();
    EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 50.00]);

    expect(fn () => app(EvaluationService::class)->setAttendanceWeight($course, 60))
        ->toThrow(ValidationException::class, '110%');
});

it('rejects an attendance weight outside the 0-100 range', function (int $weight) {
    $course = Course::factory()->create();
    EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 50.00]);

    expect(fn () => app(EvaluationService::class)->setAttendanceWeight($course, $weight))
        ->toThrow(ValidationException::class, 'entre 0% y 100%');
})->with([101, -1]);

it('rejects setCriteria when criteria sum to 100 but attendance weight is 50', function () {
    $course = Course::factory()->create();
    $course->refresh();
    expect($course->attendance_weight)->toBe(50);

    expect(fn () => app(EvaluationService::class)->setCriteria($course, [
        ['nombre' => 'Examen', 'ponderacion' => 60.0],
        ['nombre' => 'Proyecto', 'ponderacion' => 40.0],
    ]))->toThrow(ValidationException::class, '150%');
});

it('accepts setCriteria when attendance weight plus criteria sum to 100', function () {
    $course = Course::factory()->create();

    app(EvaluationService::class)->setCriteria($course, [
        ['nombre' => 'Examen', 'ponderacion' => 30.0],
        ['nombre' => 'Proyecto', 'ponderacion' => 20.0],
    ]);

    expect((float) $course->evaluationCriteria()->sum('ponderacion'))->toBe(50.0);
});

it('enforces the combined sum when adding a criterion', function () {
    $course = Course::factory()->create();
    EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 30.00]);
    $service = app(EvaluationService::class);

    $service->addCriteria($course, 'Práctica', 20);
    expect((float) $course->evaluationCriteria()->sum('ponderacion'))->toBe(50.0);

    expect(fn () => $service->addCriteria($course, 'Extra', 10))
        ->toThrow(ValidationException::class, '110%');
});

it('enforces the combined sum when updating a criterion', function () {
    $course = Course::factory()->create();
    $criterion = EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 20.00]);
    $service = app(EvaluationService::class);

    $service->updateCriteria($criterion, 'Práctica', 50);
    expect((float) $criterion->fresh()->ponderacion)->toBe(50.0);

    expect(fn () => $service->updateCriteria($criterion->fresh(), 'Práctica', 40))
        ->toThrow(ValidationException::class, '90%');
});

it('enforces the combined sum when removing a criterion and cascades its grades', function () {
    $course = Course::factory()->create(['attendance_weight' => 100]);
    $removable = EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 0.00]);
    $keep = EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 0.00]);
    $preinscription = Preinscription::factory()->create();
    Grade::factory()->create([
        'evaluation_criteria_id' => $removable->id,
        'preinscription_id' => $preinscription->id,
    ]);
    $service = app(EvaluationService::class);

    // attendance 100 + remaining criteria 0 = 100 → removal allowed; grades cascade.
    $service->removeCriteria($removable);

    expect($removable->exists)->toBeFalse();
    expect(Grade::where('evaluation_criteria_id', $removable->id)->exists())->toBeFalse();
    expect($course->evaluationCriteria()->count())->toBe(1);
    expect($keep->exists)->toBeTrue();

    // attendance 50 + criterion 50; removing it would leave 50 + 0 = 50 → blocked.
    $course2 = Course::factory()->create(['attendance_weight' => 50]);
    $only = EvaluationCriteria::factory()->create(['course_id' => $course2->id, 'ponderacion' => 50.00]);

    expect(fn () => $service->removeCriteria($only))
        ->toThrow(ValidationException::class, '50%');

    expect($only->exists)->toBeTrue();
});

it('computes attendance score and final grade with attendance included', function () {
    $course = Course::factory()->create(['attendance_weight' => 50]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    $sessions = Session::factory()->count(4)->create(['group_id' => $group->id]);
    Attendance::factory()->presente()->create([
        'session_id' => $sessions[0]->id,
        'preinscription_id' => $preinscription->id,
    ]);
    Attendance::factory()->justificado()->create([
        'session_id' => $sessions[1]->id,
        'preinscription_id' => $preinscription->id,
    ]);

    $evaluation = EvaluationCriteria::factory()->create(['course_id' => $course->id, 'nombre' => 'Evaluación', 'ponderacion' => 20.00]);
    $game = EvaluationCriteria::factory()->create(['course_id' => $course->id, 'nombre' => 'Juego', 'ponderacion' => 30.00]);

    $service = app(EvaluationService::class);
    $service->recordGrades($preinscription, [
        $evaluation->id => 85.0,
        $game->id => 100.0,
    ]);

    // attendance: 2/4 sessions × 50% = 25; criteria: 85×20/100=17, 100×30/100=30
    expect($service->attendanceScore($preinscription))->toBe(25.0);
    expect($service->finalGrade($preinscription))->toBe(72.0);
});

it('includes attendance in the certificate grade path', function () {
    $course = Course::factory()->create(['attendance_weight' => 50]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    $sessions = Session::factory()->count(4)->create(['group_id' => $group->id]);
    Attendance::factory()->presente()->create([
        'session_id' => $sessions[0]->id,
        'preinscription_id' => $preinscription->id,
    ]);
    Attendance::factory()->justificado()->create([
        'session_id' => $sessions[1]->id,
        'preinscription_id' => $preinscription->id,
    ]);

    $evaluation = EvaluationCriteria::factory()->create(['course_id' => $course->id, 'nombre' => 'Evaluación', 'ponderacion' => 20.00]);
    $game = EvaluationCriteria::factory()->create(['course_id' => $course->id, 'nombre' => 'Juego', 'ponderacion' => 30.00]);

    $service = app(EvaluationService::class);
    $service->recordGrades($preinscription, [
        $evaluation->id => 85.0,
        $game->id => 100.0,
    ]);

    // Criteria-only math yields 47 (< 70 → ASISTENCIA). With attendance (25) the
    // unified final grade is 72 (≥ 70 → APROBACION), proving attendance is included.
    expect($service->finalGrade($preinscription))->toBe(72.0);

    $certificate = app(CertificateService::class)->generate($preinscription);
    expect($certificate->tipo)->toBe(CertificateType::APROBACION);
});

it('builds teacher sheet formulas with the course attendance weight', function (int $weight) {
    $course = Course::factory()->create(['attendance_weight' => $weight]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    Session::factory()->count(2)->create(['group_id' => $group->id]);
    EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 40.00]);
    EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 60.00]);

    $rows = (new SheetExportBuilder($group->fresh()))->buildTeacherSheetRows();

    expect($rows[4])->toContain("Puntaje /{$weight}");

    $dataRow = $rows[6];

    $puntajeCell = collect($dataRow)->first(
        fn ($cell) => is_string($cell) && str_starts_with($cell, '=ROUND((')
    );
    expect($puntajeCell)->toContain("*{$weight};2)");

    $notaFinalCell = end($dataRow);
    // 2 sessions: identity A-E, sessions F-G, asistencia H, puntaje I → attendance cell I7
    expect($notaFinalCell)->toStartWith('=ROUND(I7+')->toContain(';2');
})->with([50, 60]);

it('applies a staged evaluation config atomically via applyEvaluationConfig', function () {
    $course = Course::factory()->create(['attendance_weight' => 50]);
    $updated = EvaluationCriteria::factory()->create(['course_id' => $course->id, 'nombre' => 'Examen', 'ponderacion' => 30.00]);
    $removed = EvaluationCriteria::factory()->create(['course_id' => $course->id, 'nombre' => 'Proyecto', 'ponderacion' => 20.00]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    Grade::factory()->create([
        'evaluation_criteria_id' => $updated->id,
        'preinscription_id' => $preinscription->id,
        'nota' => 60,
    ]);
    Grade::factory()->create([
        'evaluation_criteria_id' => $removed->id,
        'preinscription_id' => $preinscription->id,
        'nota' => 80,
    ]);

    // weight 40 + updated 40 + added 20 = 100; the deleted criterion (20) is excluded.
    app(EvaluationService::class)->applyEvaluationConfig(
        $course,
        40,
        [
            ['id' => $updated->id, 'nombre' => 'Examen Final', 'ponderacion' => 40, 'op' => 'update'],
            ['id' => $removed->id, 'nombre' => 'Proyecto', 'ponderacion' => 20, 'op' => 'delete'],
            ['id' => -1, 'nombre' => 'Práctica', 'ponderacion' => 20, 'op' => 'add'],
        ],
        [$preinscription->id => [$updated->id => '90', -1 => '75']],
    );

    expect($course->fresh()->attendance_weight)->toBe(40)
        ->and($updated->fresh()->nombre)->toBe('Examen Final')
        ->and((float) $updated->fresh()->ponderacion)->toBe(40.0)
        ->and($removed->fresh())->toBeNull()
        ->and(Grade::where('evaluation_criteria_id', $removed->id)->exists())->toBeFalse();

    $created = $course->evaluationCriteria()->where('nombre', 'Práctica')->first();
    expect($created)->not->toBeNull()
        ->and((float) $created->ponderacion)->toBe(20.0);

    // The staging key (-1) resolved to the new criterion id.
    expect(Grade::query()
        ->where('preinscription_id', $preinscription->id)
        ->where('evaluation_criteria_id', $created->id)
        ->first()?->nota)->toEqual(75.0)
        ->and(Grade::query()
            ->where('preinscription_id', $preinscription->id)
            ->where('evaluation_criteria_id', $updated->id)
            ->first()?->nota)->toEqual(90.0);
});

it('rejects applyEvaluationConfig when the staged sum is not 100 and persists nothing', function () {
    $course = Course::factory()->create(['attendance_weight' => 50]);
    $criterion = EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 30.00]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    expect(fn () => app(EvaluationService::class)->applyEvaluationConfig(
        $course,
        60,
        [['id' => $criterion->id, 'nombre' => 'Examen', 'ponderacion' => 30, 'op' => 'keep']],
        [$preinscription->id => [$criterion->id => '95']],
    ))->toThrow(ValidationException::class, 'debe ser 100%');

    expect($course->fresh()->attendance_weight)->toBe(50)
        ->and((float) $criterion->fresh()->ponderacion)->toBe(30.0);
    expect(Grade::where('preinscription_id', $preinscription->id)
        ->where('evaluation_criteria_id', $criterion->id)->exists())->toBeFalse();
});

it('rejects applyEvaluationConfig when a staged grade is out of range', function () {
    $course = Course::factory()->create(['attendance_weight' => 50]);
    $criterion = EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 50.00]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    expect(fn () => app(EvaluationService::class)->applyEvaluationConfig(
        $course,
        50,
        [['id' => $criterion->id, 'nombre' => 'Examen', 'ponderacion' => 50, 'op' => 'keep']],
        [$preinscription->id => [$criterion->id => '150']],
    ))->toThrow(ValidationException::class, 'fuera del rango 0 a 100');

    expect(Grade::where('preinscription_id', $preinscription->id)
        ->where('evaluation_criteria_id', $criterion->id)->exists())->toBeFalse();
});
