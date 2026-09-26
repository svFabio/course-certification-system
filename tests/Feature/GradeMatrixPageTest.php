<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\GroupSheetsNeedRefresh;
use App\Filament\Instructor\Pages\GradeMatrix;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\EvaluationCriteria;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use App\Services\EvaluationService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::INSTRUCTOR->value]);
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
});

it('lists only the authenticated instructor courses in the course selector', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $otherInstructor = User::factory()->create();
    $otherInstructor->assignRole(UserRole::INSTRUCTOR->value);

    $ownCourse = Course::factory()->create([
        'instructor_id' => $instructor->id,
        'nombre' => 'Curso Propio del Instructor',
    ]);
    $otherCourse = Course::factory()->create([
        'instructor_id' => $otherInstructor->id,
        'nombre' => 'Curso Ajeno de Prueba',
    ]);

    $this->actingAs($instructor);

    Livewire::test(GradeMatrix::class)
        ->assertSee('Curso Propio del Instructor')
        ->assertDontSee('Curso Ajeno de Prueba')
        ->assertSet('selectedCourseId', $ownCourse->id);
});

it('renders the matrix with participants and one column per criterion', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $criterion = EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Examen Parcial Propio',
        'ponderacion' => 50.00,
    ]);
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $this->actingAs($instructor);

    Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->assertSee($preinscription->full_name)
        ->assertSee('Examen Parcial Propio')
        ->assertSee('Asistencia')
        ->assertSee('Nota Final');
});

it('stages an added criterion without errors and reports the exact sum error only on save', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 50.00]);
    Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $this->actingAs($instructor);

    // Staging an extra 30% column (total 130%) must not throw any error.
    $component = Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->call('openCreateCriteria')
        ->set('criteriaNombre', 'Evaluación Extra')
        ->set('criteriaPonderacion', 30)
        ->call('saveCriteria')
        ->assertDontSee('debe ser 100%')
        ->assertSee('Evaluación Extra')
        ->assertSee('Cambios sin guardar');

    expect($course->evaluationCriteria()->count())->toBe(1);

    // Only saveAll() validates the sum, with the exact domain message.
    $component->call('saveAll')
        ->assertSee('La ponderación total debe ser 100% (asistencia 50% + criterios 80% = 130%). Ajusta los porcentajes.');

    expect($course->evaluationCriteria()->count())->toBe(1)
        ->and($course->fresh()->attendance_weight)->toBe(50);
});

it('persists a staged valid weight and criterion combination on save', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Examen',
        'ponderacion' => 50.00,
    ]);
    Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $this->actingAs($instructor);

    // weight 40 + existing 50 + staged 10 = 100.
    $component = Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->set('attendanceWeightInput', 40)
        ->call('openCreateCriteria')
        ->set('criteriaNombre', 'Práctica')
        ->set('criteriaPonderacion', 10)
        ->call('saveCriteria')
        ->assertSee('Cambios sin guardar');

    $component
        ->call('saveAll')
        ->assertDontSee('Cambios sin guardar')
        ->assertSee('Práctica');

    expect($course->fresh()->attendance_weight)->toBe(40)
        ->and($course->evaluationCriteria()->count())->toBe(2)
        ->and($course->evaluationCriteria()->where('nombre', 'Práctica')->value('ponderacion'))->toEqual(10.00);
});

it('dispatches a sheets refresh event for every group of the course after saveAll', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $firstGroup = Group::factory()->create(['course_id' => $course->id]);
    $secondGroup = Group::factory()->create(['course_id' => $course->id]);
    EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Examen',
        'ponderacion' => 50.00,
    ]);
    Preinscription::factory()->inscrito()->create(['group_id' => $firstGroup->id]);

    $this->actingAs($instructor);

    Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $firstGroup->id)
        ->call('saveAll');

    Event::assertDispatchedTimes(GroupSheetsNeedRefresh::class, 2);
    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $firstGroup->id);
    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $secondGroup->id);
});

it('stages a nota with a live preview and persists it only on save', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $criterion = EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Examen',
        'ponderacion' => 50.00,
    ]);
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $sessions = Session::factory()->count(2)->create(['group_id' => $group->id]);
    foreach ($sessions as $session) {
        Attendance::factory()->presente()->create([
            'session_id' => $session->id,
            'preinscription_id' => $preinscription->id,
        ]);
    }

    $this->actingAs($instructor);

    // attendance: 2/2 × 50 = 50; criteria: 80 × 50 / 100 = 40 → final 90.00
    $component = Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->call('stageNota', $preinscription->id, $criterion->id, '80')
        ->assertSee('90.00')
        ->assertSee('Cambios sin guardar');

    $this->assertDatabaseMissing('grades', [
        'preinscription_id' => $preinscription->id,
        'evaluation_criteria_id' => $criterion->id,
    ]);

    $component->call('saveAll')
        ->assertSee('90.00')
        ->assertDontSee('Cambios sin guardar');

    $this->assertDatabaseHas('grades', [
        'preinscription_id' => $preinscription->id,
        'evaluation_criteria_id' => $criterion->id,
        'nota' => 80,
    ]);
});

it('recomputes the live preview when a criterion weight is staged', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $criterion = EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Examen',
        'ponderacion' => 50.00,
    ]);
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $sessions = Session::factory()->count(2)->create(['group_id' => $group->id]);
    foreach ($sessions as $session) {
        Attendance::factory()->presente()->create([
            'session_id' => $session->id,
            'preinscription_id' => $preinscription->id,
        ]);
    }

    app(EvaluationService::class)->recordGrades($preinscription, [$criterion->id => 80.0]);

    $this->actingAs($instructor);

    // Before: attendance 50 + 80 × 50 / 100 = 90.00.
    // Staging ponderación 30 → 50 + 24 = 74.00, without touching the database.
    Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->assertSee('90.00')
        ->call('openEditCriteria', $criterion->id)
        ->assertSet('criteriaPonderacion', 50)
        ->set('criteriaPonderacion', 30)
        ->call('saveCriteria')
        ->assertSee('74.00')
        ->assertSee('Cambios sin guardar')
        ->assertSee('30%');

    expect((float) $criterion->fresh()->ponderacion)->toBe(50.0);
});

it('stages an attendance weight with a live preview and persists it only on save', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 40]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $criterion = EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Examen',
        'ponderacion' => 50.00,
    ]);
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $sessions = Session::factory()->count(2)->create(['group_id' => $group->id]);
    Attendance::factory()->presente()->create([
        'session_id' => $sessions[0]->id,
        'preinscription_id' => $preinscription->id,
    ]);

    app(EvaluationService::class)->recordGrades($preinscription, [$criterion->id => 80.0]);

    $this->actingAs($instructor);

    // Before: attendance 1/2 × 40 = 20.00, final 60.00.
    // Staged weight 50 → 25.00 / 65.00 as a live preview, still not persisted.
    $component = Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->assertSee('20.00')
        ->assertSee('60.00')
        ->set('attendanceWeightInput', 50)
        ->assertSee('25.00')
        ->assertSee('65.00')
        ->assertSee('Cambios sin guardar');

    expect($course->fresh()->attendance_weight)->toBe(40);

    $component
        ->call('saveAll')
        ->assertSee('25.00')
        ->assertSee('65.00')
        ->assertDontSee('Cambios sin guardar');

    expect($course->fresh()->attendance_weight)->toBe(50);
});

it('stages a criterion removal and cascades its grades only on save', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $keep = EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Examen Final',
        'ponderacion' => 50.00,
    ]);
    $removable = EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Proyecto Extra',
        'ponderacion' => 0.00,
    ]);
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    Grade::factory()->create([
        'evaluation_criteria_id' => $removable->id,
        'preinscription_id' => $preinscription->id,
    ]);

    $this->actingAs($instructor);

    // Staging the removal only hides the column: nothing is deleted yet.
    $component = Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->assertSee('Proyecto Extra')
        ->call('removeCriteria', $removable->id)
        ->assertSee('Examen Final')
        ->assertDontSee('Proyecto Extra')
        ->assertSee('Cambios sin guardar');

    expect($course->evaluationCriteria()->count())->toBe(2);
    expect(Grade::where('evaluation_criteria_id', $removable->id)->exists())->toBeTrue();

    // Save persists the tombstone and cascades the dependent grades.
    $component->call('saveAll')
        ->assertDontSee('Cambios sin guardar');

    $this->assertDatabaseMissing('grades', [
        'evaluation_criteria_id' => $removable->id,
        'preinscription_id' => $preinscription->id,
    ]);
    expect($course->evaluationCriteria()->count())->toBe(1)
        ->and($keep->exists)->toBeTrue();
});

it('registers the grade matrix page and no longer registers the old grade resource', function () {
    $panel = Filament::getPanel('instructor');

    expect($panel->getPages())->toContain(GradeMatrix::class)
        ->and($panel->getResources())
        ->not->toContain('App\Filament\Instructor\Resources\InstructorGradeResource');
});

it('validates staged notas only on save and persists after fixing them', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $criterion = EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'ponderacion' => 50.00,
    ]);
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $this->actingAs($instructor);

    // Staging 150 does not raise any immediate error.
    $component = Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->call('stageNota', $preinscription->id, $criterion->id, '150')
        ->assertDontSee('fuera del rango')
        ->assertSee('Cambios sin guardar');

    // saveAll() collects the error with the row name and persists nothing.
    $component->call('saveAll')
        ->assertSee($preinscription->full_name.': la nota 150 está fuera del rango 0 a 100.');

    $this->assertDatabaseMissing('grades', [
        'preinscription_id' => $preinscription->id,
        'evaluation_criteria_id' => $criterion->id,
    ]);

    // Fixing the staged value and saving again persists it.
    $component
        ->call('stageNota', $preinscription->id, $criterion->id, '95')
        ->call('saveAll')
        ->assertDontSee('fuera del rango');

    $this->assertDatabaseHas('grades', [
        'preinscription_id' => $preinscription->id,
        'evaluation_criteria_id' => $criterion->id,
        'nota' => 95,
    ]);
});

it('shows the dirty badge while staged changes exist and discards them back to database state', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Examen',
        'ponderacion' => 50.00,
    ]);
    Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $this->actingAs($instructor);

    $component = Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->assertDontSee('Cambios sin guardar');

    $component
        ->set('attendanceWeightInput', 60)
        ->assertSee('Cambios sin guardar')
        ->call('openCreateCriteria')
        ->set('criteriaNombre', 'Extra')
        ->set('criteriaPonderacion', 10)
        ->call('saveCriteria')
        ->assertSee('Cambios sin guardar')
        ->call('discardChanges')
        ->assertDontSee('Cambios sin guardar')
        ->assertSet('attendanceWeightInput', 50)
        ->assertSet('pendingNotas', []);

    expect($course->fresh()->attendance_weight)->toBe(50)
        ->and($course->evaluationCriteria()->count())->toBe(1);
});

it('shows the staged attendance weight in the Asistencia column header', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Examen',
        'ponderacion' => 50.00,
    ]);
    Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    Session::factory()->count(2)->create(['group_id' => $group->id]);

    $this->actingAs($instructor);

    Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->assertSee('50%')
        ->set('attendanceWeightInput', 45)
        ->assertSee('45%');

    expect($course->fresh()->attendance_weight)->toBe(50);
});

it('blocks group switching while there are unsaved changes until confirmed', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $groupOne = Group::factory()->create(['course_id' => $course->id]);
    $groupTwo = Group::factory()->create(['course_id' => $course->id]);
    EvaluationCriteria::factory()->create(['course_id' => $course->id, 'ponderacion' => 50.00]);

    $this->actingAs($instructor);

    $component = Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $groupOne->id)
        ->set('attendanceWeightInput', 60)
        ->assertSee('Cambios sin guardar');

    // The switch is blocked: selection reverts and the confirm modal opens.
    $component
        ->set('selectedGroupId', $groupTwo->id)
        ->assertSet('selectedGroupId', $groupOne->id)
        ->assertSet('switchConfirmOpen', true)
        ->assertSee('Tiene cambios sin guardar. Si cambia de selección se descartarán.');

    // "Seguir aquí": keep the selection and the staged changes.
    $component
        ->call('cancelSwitch')
        ->assertSet('switchConfirmOpen', false)
        ->assertSet('selectedGroupId', $groupOne->id)
        ->assertSee('Cambios sin guardar');

    // "Continuar y descartar": apply the target selection and drop the staged state.
    $component
        ->set('selectedGroupId', $groupTwo->id)
        ->call('confirmSwitch')
        ->assertSet('selectedGroupId', $groupTwo->id)
        ->assertSet('switchConfirmOpen', false)
        ->assertSet('attendanceWeightInput', 50)
        ->assertDontSee('Cambios sin guardar');

    expect($course->fresh()->attendance_weight)->toBe(50);
});

it('blocks course switching while there are unsaved changes until confirmed', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $courseOne = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $groupOne = Group::factory()->create(['course_id' => $courseOne->id]);
    EvaluationCriteria::factory()->create(['course_id' => $courseOne->id, 'ponderacion' => 50.00]);

    $courseTwo = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 30]);
    Group::factory()->create(['course_id' => $courseTwo->id]);
    EvaluationCriteria::factory()->create(['course_id' => $courseTwo->id, 'ponderacion' => 70.00]);

    $this->actingAs($instructor);

    $component = Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $courseOne->id)
        ->set('attendanceWeightInput', 60)
        ->assertSee('Cambios sin guardar');

    $component
        ->set('selectedCourseId', $courseTwo->id)
        ->assertSet('selectedCourseId', $courseOne->id)
        ->assertSet('switchConfirmOpen', true)
        ->assertSee('Tiene cambios sin guardar. Si cambia de selección se descartarán.')
        ->call('confirmSwitch')
        ->assertSet('selectedCourseId', $courseTwo->id)
        ->assertSet('switchConfirmOpen', false)
        ->assertSet('attendanceWeightInput', 30)
        ->assertDontSee('Cambios sin guardar');

    expect($courseOne->fresh()->attendance_weight)->toBe(50)
        ->and($courseTwo->fresh()->attendance_weight)->toBe(30);
});

it('renders the attendance cell as score plus attended sessions count', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Examen',
        'ponderacion' => 50.00,
    ]);
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $sessions = Session::factory()->count(2)->create(['group_id' => $group->id]);
    Attendance::factory()->presente()->create([
        'session_id' => $sessions[0]->id,
        'preinscription_id' => $preinscription->id,
    ]);

    $this->actingAs($instructor);

    // attendance: 1/2 × 50 = 25.00 → cell renders "25.00 (1/2)", never "50% · 25.00".
    $component = Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->assertSet('rows.0.attendance_attended', 1)
        ->assertSee('25.00')
        ->assertSee('(1/2)');

    $text = preg_replace('/\s+/', ' ', strip_tags($component->html()));

    expect($text)->toContain('25.00 (1/2)')
        ->and($text)->not->toContain('% ·');
});

it('renders the attended sessions count as (0/0) when the group has no sessions', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id, 'attendance_weight' => 50]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Examen',
        'ponderacion' => 50.00,
    ]);
    Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $this->actingAs($instructor);

    $component = Livewire::test(GradeMatrix::class)
        ->set('selectedCourseId', $course->id)
        ->set('selectedGroupId', $group->id)
        ->assertSet('rows.0.attendance_attended', 0)
        ->assertSet('sessionCount', 0);

    $text = preg_replace('/\s+/', ' ', strip_tags($component->html()));

    expect($text)->toContain('0.00 (0/0)');
});
