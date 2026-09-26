<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Instructor\Resources\InstructorSessionResource\Pages\CreateInstructorSession;
use App\Filament\Instructor\Resources\InstructorSessionResource\Pages\EditInstructorSession;
use App\Filament\Instructor\Resources\InstructorSessionResource\Pages\ListInstructorSessions;
use App\Models\Course;
use App\Models\Group;
use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::INSTRUCTOR->value]);

    // Resource route names (view/edit/delete) resolve against the current panel.
    Filament::setCurrentPanel(Filament::getPanel('instructor'));
});

it('lists only the sessions owned by the authenticated instructor', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $otherInstructor = User::factory()->create();
    $otherInstructor->assignRole(UserRole::INSTRUCTOR->value);

    $ownCourse = Course::factory()->create([
        'instructor_id' => $instructor->id,
        'nombre' => 'Curso Propio de Sesiones',
    ]);
    $ownGroup = Group::factory()->create(['course_id' => $ownCourse->id]);
    $ownSession = Session::factory()->create(['group_id' => $ownGroup->id]);

    $otherCourse = Course::factory()->create([
        'instructor_id' => $otherInstructor->id,
        'nombre' => 'Curso Ajeno de Sesiones',
    ]);
    $otherGroup = Group::factory()->create(['course_id' => $otherCourse->id]);
    $otherSession = Session::factory()->create(['group_id' => $otherGroup->id]);

    $this->actingAs($instructor);

    Livewire::test(ListInstructorSessions::class)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$ownSession])
        ->assertCanNotSeeTableRecords([$otherSession])
        ->assertSee('Curso Propio de Sesiones')
        ->assertDontSee('Curso Ajeno de Sesiones');
});

it('renders the estado badge following the dictada, reprogramada, programada priority', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
        'nombre' => 'Curso Badges de Sesiones',
    ]);
    $group = Group::factory()->create(['course_id' => $course->id]);

    $dictada = Session::factory()->dictada()->create(['group_id' => $group->id]);
    $reprogramada = Session::factory()->create([
        'group_id' => $group->id,
        'dictada' => false,
        'es_pospuesta' => true,
    ]);
    $programada = Session::factory()->upcoming()->create([
        'group_id' => $group->id,
        'dictada' => false,
        'es_pospuesta' => false,
    ]);

    $this->actingAs($instructor);

    $component = Livewire::test(ListInstructorSessions::class)
        ->assertTableColumnStateSet('estado', 'Dictada', $dictada)
        ->assertTableColumnStateSet('estado', 'Reprogramada', $reprogramada)
        ->assertTableColumnStateSet('estado', 'Programada', $programada);

    // The filter <select> also contains these labels, so assertions are scoped to the table body.
    preg_match('/<tbody.*<\/tbody>/s', $component->html(), $matches);
    $tableBody = $matches[0] ?? '';

    expect($tableBody)
        ->toContain('fi-badge')
        ->toContain('fi-color-success')
        ->toContain('fi-color-warning')
        ->toContain('fi-color-info')
        ->toContain('Dictada')
        ->toContain('Reprogramada')
        ->toContain('Programada');
});

it('no longer renders the internal created_at registration column', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    Session::factory()->create(['group_id' => $group->id]);

    $this->actingAs($instructor);

    Livewire::test(ListInstructorSessions::class)
        ->assertDontSee('Fecha de registro')
        ->assertTableColumnDoesNotExist('created_at');
});

/**
 * @return array{instructor: User, ownGroup: Group, otherGroup: Group}
 */
function sessionResourceTwoInstructorFixture(): array
{
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $otherInstructor = User::factory()->create();
    $otherInstructor->assignRole(UserRole::INSTRUCTOR->value);

    $ownCourse = Course::factory()->create(['instructor_id' => $instructor->id]);
    $ownGroup = Group::factory()->create(['course_id' => $ownCourse->id]);

    $otherCourse = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
    $otherGroup = Group::factory()->create(['course_id' => $otherCourse->id]);

    return compact('instructor', 'ownGroup', 'otherGroup');
}

it('rejects creating a session in a group owned by another instructor', function () {
    $data = sessionResourceTwoInstructorFixture();

    $this->actingAs($data['instructor']);

    $sessionCountBefore = Session::query()->count();

    Livewire::test(CreateInstructorSession::class)
        ->fillForm([
            'group_id' => $data['otherGroup']->id,
            'fecha' => '2026-12-01',
            'hora_inicio' => '09:00',
            'hora_fin' => '11:00',
            'dictada' => false,
            'motivo_reprogramacion' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['group_id']);

    expect(Session::query()->count())->toBe($sessionCountBefore);
    expect(Session::query()->where('group_id', $data['otherGroup']->id)->count())->toBe(0);
});

it('rejects moving an own session into a group owned by another instructor', function () {
    $data = sessionResourceTwoInstructorFixture();

    $ownSession = Session::factory()->create(['group_id' => $data['ownGroup']->id]);

    $this->actingAs($data['instructor']);

    Livewire::test(EditInstructorSession::class, ['record' => $ownSession->getKey()])
        ->fillForm(['group_id' => $data['otherGroup']->id])
        ->call('save')
        ->assertHasFormErrors(['group_id']);

    expect($ownSession->fresh()->group_id)->toBe($data['ownGroup']->id);
});

it('still allows creating and editing sessions on the instructor own group', function () {
    $data = sessionResourceTwoInstructorFixture();

    $this->actingAs($data['instructor']);

    Livewire::test(CreateInstructorSession::class)
        ->fillForm([
            'group_id' => $data['ownGroup']->id,
            'fecha' => '2026-12-02',
            'hora_inicio' => '09:00',
            'hora_fin' => '11:00',
            'dictada' => false,
            'motivo_reprogramacion' => null,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = Session::query()
        ->where('group_id', $data['ownGroup']->id)
        ->where('fecha', '2026-12-02')
        ->first();

    expect($created)->not->toBeNull();

    Livewire::test(EditInstructorSession::class, ['record' => $created->getKey()])
        ->fillForm([
            'group_id' => $data['ownGroup']->id,
            'hora_inicio' => '10:00',
            'hora_fin' => '12:00',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $created->refresh();

    expect($created->group_id)->toBe($data['ownGroup']->id);
    expect($created->hora_inicio->format('H:i'))->toBe('10:00');
});

it('postpones a session through the table action with a valid weekday', function () {
    $data = sessionResourceTwoInstructorFixture();

    $session = Session::factory()->create([
        'group_id' => $data['ownGroup']->id,
        'fecha' => now()->addDays(2)->format('Y-m-d'),
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    $this->actingAs($data['instructor']);

    $targetDate = Carbon::today()->next(Carbon::MONDAY);

    Livewire::test(ListInstructorSessions::class)
        ->callTableAction('postpone', $session, [
            'nueva_fecha' => $targetDate->format('Y-m-d'),
            'motivo' => 'Emergencia del instructor',
        ])
        ->assertHasNoTableActionErrors();

    $session->refresh();

    expect($session->es_pospuesta)->toBeTrue();
    expect($session->fecha->toDateString())->toBe($targetDate->toDateString());
    expect($session->motivo_reprogramacion)->toBe('Emergencia del instructor');
});

it('rejects postposing via the table action to a weekend date', function () {
    $data = sessionResourceTwoInstructorFixture();

    $session = Session::factory()->create([
        'group_id' => $data['ownGroup']->id,
        'fecha' => now()->addDays(2)->format('Y-m-d'),
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    $this->actingAs($data['instructor']);

    $saturday = Carbon::today()->next(Carbon::SATURDAY);

    Livewire::test(ListInstructorSessions::class)
        ->callTableAction('postpone', $session, [
            'nueva_fecha' => $saturday->format('Y-m-d'),
            'motivo' => 'Cambio de horario',
        ])
        ->assertHasTableActionErrors(['nueva_fecha']);

    expect($session->fresh()->es_pospuesta)->toBeFalse();
});
