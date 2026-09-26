<?php

declare(strict_types=1);

use App\Enums\AttendanceScanResult;
use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('registers attendance successfully when student is within radius', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create(['instructor_id' => $user->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $user->email,
    ]);

    $response = $this->actingAs($user)
        ->withoutMiddleware(ValidateCsrfToken::class)
        ->postJson('/api/asistencia/registrar', [
            'session_id' => $session->id,
            'lat' => -17.78331,
            'lng' => -66.15001,
        ]);

    $response->assertOk()
        ->assertJson([
            'status' => AttendanceScanResult::PRESENTE->value,
            'attendance_status' => AttendanceStatus::PRESENTE->value,
        ]);

    $this->assertDatabaseHas('attendances', [
        'session_id' => $session->id,
        'preinscription_id' => $preinscription->id,
        'status' => AttendanceStatus::PRESENTE->value,
    ]);
});

it('flags attendance for review when student is outside radius', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create(['instructor_id' => $user->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $user->email,
    ]);

    // Coordinates far from lab
    $response = $this->actingAs($user)
        ->withoutMiddleware(ValidateCsrfToken::class)
        ->postJson('/api/asistencia/registrar', [
            'session_id' => $session->id,
            'lat' => -17.8000,
            'lng' => -66.2000,
        ]);

    $response->assertOk()
        ->assertJson([
            'status' => AttendanceScanResult::PARA_REVISION->value,
            'attendance_status' => AttendanceStatus::AUSENTE->value,
        ]);

    $this->assertDatabaseHas('attendances', [
        'session_id' => $session->id,
        'preinscription_id' => $preinscription->id,
        'status' => AttendanceStatus::AUSENTE->value,
    ]);
});

it('rejects attendance registration when the instructor does not own the session group', function () {
    $instructorA = User::factory()->create();
    $instructorB = User::factory()->create();

    $course = Course::factory()->create(['instructor_id' => $instructorA->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);

    // Resolvable for instructor B on purpose: only the ownership check can stop this request.
    Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $instructorB->email,
    ]);

    $response = $this->actingAs($instructorB)
        ->withoutMiddleware(ValidateCsrfToken::class)
        ->postJson('/api/asistencia/registrar', [
            'session_id' => $session->id,
            'lat' => -17.78331,
            'lng' => -66.15001,
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'No tiene permisos para registrar asistencia en esta sesión.',
        ]);

    $this->assertDatabaseMissing('attendances', ['session_id' => $session->id]);
});

it('allows an admin to register attendance on any session group', function () {
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);

    $instructor = User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $instructor->email,
    ]);

    $response = $this->actingAs($admin)
        ->withoutMiddleware(ValidateCsrfToken::class)
        ->postJson('/api/asistencia/registrar', [
            'session_id' => $session->id,
            'preinscription_id' => $preinscription->id,
            'lat' => -17.78331,
            'lng' => -66.15001,
        ]);

    $response->assertOk();

    $this->assertDatabaseHas('attendances', [
        'session_id' => $session->id,
        'preinscription_id' => $preinscription->id,
        'status' => AttendanceStatus::PRESENTE->value,
    ]);
});

it('rejects a preinscription that does not belong to the session group', function () {
    $instructor = User::factory()->create();

    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);

    $foreignPreinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => Group::factory()->create()->id,
    ]);

    $response = $this->actingAs($instructor)
        ->withoutMiddleware(ValidateCsrfToken::class)
        ->postJson('/api/asistencia/registrar', [
            'session_id' => $session->id,
            'preinscription_id' => $foreignPreinscription->id,
            'lat' => -17.78331,
            'lng' => -66.15001,
        ]);

    $response->assertStatus(422);

    $this->assertDatabaseMissing('attendances', ['session_id' => $session->id]);
});

it('rejects out of range coordinates before geolocation math', function () {
    $instructor = User::factory()->create();

    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);

    Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $instructor->email,
    ]);

    $response = $this->actingAs($instructor)
        ->withoutMiddleware(ValidateCsrfToken::class)
        ->postJson('/api/asistencia/registrar', [
            'session_id' => $session->id,
            'lat' => 999,
            'lng' => -66.15001,
        ]);

    $response->assertStatus(422);

    $this->assertDatabaseMissing('attendances', ['session_id' => $session->id]);
});
