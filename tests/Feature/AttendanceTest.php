<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers attendance successfully when student is within radius', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $session = Session::factory()->create(['group_id' => $group->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $user->email,
    ]);

    // Coordinates close to lab (-17.7833, -66.1500)
    $response = $this->actingAs($user)->postJson('/api/asistencia/registrar', [
        'session_id' => $session->id,
        'lat' => -17.78331,
        'lng' => -66.15001,
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'presente',
            'attendance_status' => 'presente',
        ]);

    $this->assertDatabaseHas('attendances', [
        'session_id' => $session->id,
        'preinscription_id' => $preinscription->id,
        'status' => AttendanceStatus::PRESENTE->value,
    ]);
});

it('flags attendance for review when student is outside radius', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $session = Session::factory()->create(['group_id' => $group->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $user->email,
    ]);

    // Coordinates far from lab
    $response = $this->actingAs($user)->postJson('/api/asistencia/registrar', [
        'session_id' => $session->id,
        'lat' => -17.8000,
        'lng' => -66.2000,
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'para_revision',
            'attendance_status' => 'ausente',
        ]);

    $this->assertDatabaseHas('attendances', [
        'session_id' => $session->id,
        'preinscription_id' => $preinscription->id,
        'status' => AttendanceStatus::AUSENTE->value,
    ]);
});
