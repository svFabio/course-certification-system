<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\PreinscriptionStatus;
use App\Enums\UserRole;
use App\Events\GroupSheetsNeedRefresh;
use App\Filament\Instructor\Pages\MarkAttendance;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::INSTRUCTOR->value]);
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
});

it('renders the mark attendance page for an instructor', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    Session::factory()->count(3)->create(['group_id' => $group->id]);

    $this->actingAs($instructor)
        ->get('/instructor/mark-attendance')
        ->assertSuccessful()
        ->assertSee('Marcar Asistencia');
});

it('allows instructor to select group and save attendance via livewire', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);

    $preinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    $this->actingAs($instructor);

    $key = "{$preinscription->id}_{$session->id}";

    Livewire::test(MarkAttendance::class)
        ->set('selectedGroupId', (string) $group->id)
        ->call('markAttendance', $key, AttendanceStatus::PRESENTE->value)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('attendances', [
        'session_id' => $session->id,
        'preinscription_id' => $preinscription->id,
        'status' => AttendanceStatus::PRESENTE->value,
    ]);
});

it('dispatches a sheets refresh event when attendance is saved', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);

    $preinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    $this->actingAs($instructor);

    $key = "{$preinscription->id}_{$session->id}";

    Livewire::test(MarkAttendance::class)
        ->set('selectedGroupId', (string) $group->id)
        ->call('markAttendance', $key, AttendanceStatus::PRESENTE->value)
        ->call('save')
        ->assertHasNoErrors();

    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $group->id);
});

/**
 * @return array{instructorB: User, groupA: Group, sessionA: Session, preinscriptionA: Preinscription, groupB: Group, sessionB: Session, preinscriptionB: Preinscription}
 */
function markAttendanceTwoInstructorFixture(): array
{
    $instructorA = User::factory()->create();
    $instructorA->assignRole(UserRole::INSTRUCTOR->value);
    $instructorB = User::factory()->create();
    $instructorB->assignRole(UserRole::INSTRUCTOR->value);

    $groupA = Group::factory()->create([
        'course_id' => Course::factory()->create(['instructor_id' => $instructorA->id])->id,
    ]);
    $sessionA = Session::factory()->create(['group_id' => $groupA->id]);
    $preinscriptionA = Preinscription::factory()->create([
        'group_id' => $groupA->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    $groupB = Group::factory()->create([
        'course_id' => Course::factory()->create(['instructor_id' => $instructorB->id])->id,
    ]);
    $sessionB = Session::factory()->create(['group_id' => $groupB->id]);
    $preinscriptionB = Preinscription::factory()->create([
        'group_id' => $groupB->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    return compact(
        'instructorB',
        'groupA',
        'sessionA',
        'preinscriptionA',
        'groupB',
        'sessionB',
        'preinscriptionB',
    );
}

it('blocks loading and saving attendance when a foreign group id is pushed into selectedGroupId', function () {
    $data = markAttendanceTwoInstructorFixture();

    $this->actingAs($data['instructorB']);

    $foreignKey = "{$data['preinscriptionA']->id}_{$data['sessionA']->id}";

    Livewire::test(MarkAttendance::class)
        ->set('selectedGroupId', $data['groupA']->id)
        ->assertSet('sessions', [])
        ->assertSet('students', [])
        ->call('markAttendance', $foreignKey, AttendanceStatus::PRESENTE->value)
        ->call('save')
        ->assertNotified('El grupo seleccionado no existe o no pertenece a sus cursos.');

    $this->assertDatabaseMissing('attendances', [
        'session_id' => $data['sessionA']->id,
        'preinscription_id' => $data['preinscriptionA']->id,
    ]);
});

it('ignores a foreign group query parameter on mount and saves nothing', function () {
    $data = markAttendanceTwoInstructorFixture();

    $this->actingAs($data['instructorB']);

    $foreignKey = "{$data['preinscriptionA']->id}_{$data['sessionA']->id}";

    Livewire::withQueryParams(['group' => $data['groupA']->id])
        ->test(MarkAttendance::class)
        ->assertSet('selectedGroupId', $data['groupB']->id)
        ->assertSet('sessions', fn (array $sessions): bool => array_column($sessions, 'id') === [$data['sessionB']->id])
        ->call('markAttendance', $foreignKey, AttendanceStatus::PRESENTE->value)
        ->call('save');

    $this->assertDatabaseMissing('attendances', [
        'session_id' => $data['sessionA']->id,
        'preinscription_id' => $data['preinscriptionA']->id,
    ]);
});

it('rejects the whole batch when attendance rows reference a foreign session or preinscription', function () {
    $data = markAttendanceTwoInstructorFixture();

    $this->actingAs($data['instructorB']);

    $ownKey = "{$data['preinscriptionB']->id}_{$data['sessionB']->id}";
    $foreignKey = "{$data['preinscriptionA']->id}_{$data['sessionA']->id}";

    Livewire::test(MarkAttendance::class)
        ->set('selectedGroupId', $data['groupB']->id)
        ->set('attendanceData', [
            $ownKey => AttendanceStatus::PRESENTE->value,
            $foreignKey => AttendanceStatus::PRESENTE->value,
        ])
        ->call('save')
        ->assertNotified('Fila de asistencia ajena al grupo seleccionado.');

    $this->assertDatabaseCount('attendances', 0);
});
