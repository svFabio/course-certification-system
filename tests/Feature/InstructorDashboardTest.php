<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Instructor\Pages\InstructorDashboard;
use App\Filament\Instructor\Widgets\InstructorStatsOverviewWidget;
use App\Filament\Instructor\Widgets\PendingAttendanceWidget;
use App\Filament\Instructor\Widgets\UpcomingSessionsWidget;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::INSTRUCTOR->value]);
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
});

function makeInstructorGroup(User $instructor): Group
{
    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);

    return $group;
}

it('mounts the dashboard page for an authenticated instructor', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    makeInstructorGroup($instructor);

    $this->actingAs($instructor);

    Livewire::test(InstructorDashboard::class)->assertSuccessful();

    $this->get(route('filament.instructor.pages.instructor-dashboard'))
        ->assertOk()
        ->assertSee('Mi Panel');
});

it('counts only inscrito and pendiente_pago preinscriptions as students', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $group = makeInstructorGroup($instructor);

    Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    Preinscription::factory()->pendiente()->create(['group_id' => $group->id]);
    Preinscription::factory()->retirado()->create(['group_id' => $group->id]);

    expect($group->preinscriptions()->count())->toBe(3);

    $this->actingAs($instructor);

    // retirado is excluded → only 2 of the 3 preinscriptions are counted.
    Livewire::test(InstructorStatsOverviewWidget::class)
        ->assertSee('Estudiantes inscritos')
        ->assertSee('2');
});

it('shows upcoming sessions in the next 14 days', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $group = makeInstructorGroup($instructor);

    $session = Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => now()->addDays(3)->toDateString(),
        'hora_inicio' => '10:00',
        'hora_fin' => '12:00',
        'dictada' => false,
    ]);

    $this->actingAs($instructor);

    Livewire::test(UpcomingSessionsWidget::class)
        ->assertCanSeeTableRecords([$session])
        ->assertSee(now()->addDays(3)->format('d/m/Y'));
});

it('does not show past sessions in the upcoming list', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $group = makeInstructorGroup($instructor);

    $pastSession = Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => now()->subDay()->toDateString(),
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'dictada' => false,
    ]);

    $this->actingAs($instructor);

    Livewire::test(UpcomingSessionsWidget::class)
        ->assertCanNotSeeTableRecords([$pastSession]);
});

it('lists past sessions that still need attendance with a link to mark them', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $group = makeInstructorGroup($instructor);

    $session = Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => now()->subDay()->toDateString(),
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'dictada' => false,
    ]);

    $this->actingAs($instructor);

    Livewire::test(PendingAttendanceWidget::class)
        ->assertCanSeeTableRecords([$session])
        ->assertSee(route('filament.instructor.pages.mark-attendance', ['group' => $group->id]));
});

it('counts sessions awaiting attendance in the stats widget', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $group = makeInstructorGroup($instructor);

    Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => now()->subDay()->toDateString(),
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
        'dictada' => false,
    ]);

    $this->actingAs($instructor);

    Livewire::test(InstructorStatsOverviewWidget::class)
        ->assertSee('Sesiones por marcar')
        ->assertSee('1');
});
