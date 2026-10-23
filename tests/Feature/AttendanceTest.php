<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::INSTRUCTOR->value]);
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
});

it('authorizes instructor owner and admin to manage session attendance', function () {
    $service = app(AttendanceService::class);

    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $otherInstructor = User::factory()->create();
    $otherInstructor->assignRole(UserRole::INSTRUCTOR->value);

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);

    expect($service->canManageSession($session, $instructor))->toBeTrue();
    expect($service->canManageSession($session, $admin))->toBeTrue();
    expect($service->canManageSession($session, $otherInstructor))->toBeFalse();
    expect($service->canManageSession($session, null))->toBeFalse();
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

it('persists a manual attendance batch atomically for the interactive matrix', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $sessions = Session::factory()->count(2)->create(['group_id' => $group->id]);
    $p1 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    $p2 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $data = [
        "{$p1->id}_{$sessions[0]->id}" => AttendanceStatus::PRESENTE->value,
        "{$p2->id}_{$sessions[0]->id}" => AttendanceStatus::JUSTIFICADO->value,
        "{$p1->id}_{$sessions[1]->id}" => AttendanceStatus::AUSENTE->value,
    ];

    app(AttendanceService::class)->registerManualBatch($group, $data);

    $a1 = Attendance::where('session_id', $sessions[0]->id)->where('preinscription_id', $p1->id)->first();
    expect($a1)->not->toBeNull();
    expect($a1->status)->toBe(AttendanceStatus::PRESENTE);

    $a2 = Attendance::where('session_id', $sessions[0]->id)->where('preinscription_id', $p2->id)->first();
    expect($a2)->not->toBeNull();
    expect($a2->status)->toBe(AttendanceStatus::JUSTIFICADO);

    $a3 = Attendance::where('session_id', $sessions[1]->id)->where('preinscription_id', $p1->id)->first();
    expect($a3)->not->toBeNull();
    expect($a3->status)->toBe(AttendanceStatus::AUSENTE);
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

it('calculates session attendance statistics accurately', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);

    $p1 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    $p2 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    $p3 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    Attendance::factory()->presente()->create(['session_id' => $session->id, 'preinscription_id' => $p1->id]);
    Attendance::factory()->create(['session_id' => $session->id, 'preinscription_id' => $p2->id, 'status' => AttendanceStatus::AUSENTE]);
    Attendance::factory()->create(['session_id' => $session->id, 'preinscription_id' => $p3->id, 'status' => AttendanceStatus::JUSTIFICADO]);

    $stats = app(AttendanceService::class)->getAttendanceStats($session);

    expect($stats['total'])->toBe(3);
    expect($stats['presentes'])->toBe(1);
    expect($stats['ausentes'])->toBe(1);
    expect($stats['justificados'])->toBe(1);
});
