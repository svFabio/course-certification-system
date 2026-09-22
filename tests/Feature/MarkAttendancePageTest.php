<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\PreinscriptionStatus;
use App\Filament\Instructor\Pages\MarkAttendance;
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
    Role::firstOrCreate(['name' => 'instructor']);
    Role::firstOrCreate(['name' => 'admin']);
});

it('renders the mark attendance page for an instructor', function () {
    $instructor = User::factory()->create();
    $instructor->assignRole('instructor');

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
    $instructor->assignRole('instructor');

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
