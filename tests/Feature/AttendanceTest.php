<?php

declare(strict_types=1);

use App\Enums\AttendanceScanResult;
use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('registers attendance successfully via qr scan without requiring coordinates', function () {
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

it('registers attendance with optional coordinates when provided', function () {
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
            'lat' => -17.3938,
            'lng' => -66.1470,
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
        'lat' => -17.3938,
        'lng' => -66.1470,
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

it('persists a manual attendance batch atomically and clears prior gps coordinates', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $sessions = Session::factory()->count(2)->create(['group_id' => $group->id]);
    $p1 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    $p2 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    // Pre-existing attendance with GPS coordinates for p1
    Attendance::factory()->create([
        'session_id' => $sessions[0]->id,
        'preinscription_id' => $p1->id,
        'status' => AttendanceStatus::AUSENTE,
        'lat' => -17.3935,
        'lng' => -66.1450,
        'distancia_metros' => 50.0,
    ]);

    $data = [
        "{$p1->id}_{$sessions[0]->id}" => AttendanceStatus::PRESENTE->value,
        "{$p2->id}_{$sessions[0]->id}" => AttendanceStatus::JUSTIFICADO->value,
        "{$p1->id}_{$sessions[1]->id}" => AttendanceStatus::PRESENTE->value,
    ];

    app(AttendanceService::class)->registerManualBatch($group, $data);

    $a1 = Attendance::where('session_id', $sessions[0]->id)->where('preinscription_id', $p1->id)->first();
    expect($a1)->not->toBeNull();
    expect($a1->status)->toBe(AttendanceStatus::PRESENTE);
    expect($a1->lat)->toBeNull();
    expect($a1->lng)->toBeNull();
    expect($a1->distancia_metros)->toBeNull();

    $a2 = Attendance::where('session_id', $sessions[0]->id)->where('preinscription_id', $p2->id)->first();
    expect($a2->status)->toBe(AttendanceStatus::JUSTIFICADO);

    $a3 = Attendance::where('session_id', $sessions[1]->id)->where('preinscription_id', $p1->id)->first();
    expect($a3->status)->toBe(AttendanceStatus::PRESENTE);
});

it('deletes attendance when status is blank in manual batch', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);
    $p1 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    Attendance::factory()->presente()->create([
        'session_id' => $session->id,
        'preinscription_id' => $p1->id,
    ]);

    expect(Attendance::where('session_id', $session->id)->count())->toBe(1);

    app(AttendanceService::class)->registerManualBatch($group, [
        "{$p1->id}_{$session->id}" => '',
    ]);

    expect(Attendance::where('session_id', $session->id)->count())->toBe(0);
});

it('finds preinscription by CI regardless of case and trimming', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $baseCi = (string) fake()->numerify('#######');
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'ci' => $baseCi.'-1A',
    ]);

    $service = app(AttendanceService::class);

    $found = $service->findPreinscription(['ci' => ' '.$baseCi.'-1a '], $group->id);
    expect($found)->not->toBeNull();
    expect($found->id)->toBe($preinscription->id);
});
