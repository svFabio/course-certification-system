<?php

declare(strict_types=1);

use App\Enums\GroupStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Group;
use App\Models\Holiday;
use App\Models\Session;
use App\Models\User;
use App\Services\HolidayService;
use App\Support\BusinessRules;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('defines session durations of 1.5h for 20h courses and 2.5h for 30h courses', function () {
    expect(BusinessRules::SESSION_DURATION_HOURS)->toBe([
        '20' => 1.5,
        '30' => 2.5,
    ]);
});

it('rejects postponing a session to a holiday date', function () {
    $holidayDate = Carbon::today()->next(Carbon::TUESDAY);

    Holiday::create([
        'nombre' => 'Feriado de prueba',
        'fecha' => $holidayDate->toDateString(),
        'alcance' => 'nacional',
    ]);

    $session = Session::factory()->create([
        'fecha' => Carbon::today()->addDays(3)->toDateString(),
    ]);

    $service = app(HolidayService::class);

    expect(fn () => $service->postponeSession($session, $holidayDate, 'Feriado sobrevenido'))
        ->toThrow(
            ValidationException::class,
            'La nueva fecha no puede caer en feriado ni en fin de semana.'
        );
});

it('rejects postponing a session to a saturday', function () {
    $session = Session::factory()->create();

    $service = app(HolidayService::class);

    expect(fn () => $service->postponeSession($session, Carbon::today()->next(Carbon::SATURDAY), 'Cambio de horario'))
        ->toThrow(
            ValidationException::class,
            'La nueva fecha no puede caer en feriado ni en fin de semana.'
        );
});

it('rejects postponing a session to a sunday', function () {
    $session = Session::factory()->create();

    $service = app(HolidayService::class);

    expect(fn () => $service->postponeSession($session, Carbon::today()->next(Carbon::SUNDAY), 'Cambio de horario'))
        ->toThrow(
            ValidationException::class,
            'La nueva fecha no puede caer en feriado ni en fin de semana.'
        );
});

it('postpones a session to a valid weekday and records the rescheduling metadata', function () {
    $session = Session::factory()->create([
        'fecha' => Carbon::today()->addDays(2)->toDateString(),
    ]);
    $originalDate = $session->fecha->toDateString();
    $targetDate = Carbon::today()->next(Carbon::MONDAY);

    $service = app(HolidayService::class);
    $result = $service->postponeSession($session, $targetDate, 'Emerencia del instructor');

    expect($result->es_pospuesta)->toBeTrue();
    expect($result->fecha->toDateString())->toBe($targetDate->toDateString());
    expect($result->fecha_original->toDateString())->toBe($originalDate);
    expect($result->motivo_reprogramacion)->toBe('Emerencia del instructor');
});

it('warns admins when a holiday is registered over a future session in an active group', function () {
    $date = Carbon::today()->next(Carbon::MONDAY);

    $group = Group::factory()->create(['status' => GroupStatus::HABILITADO]);
    Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => $date->toDateString(),
    ]);

    Holiday::create([
        'nombre' => 'Feriado de Prueba UMSS',
        'fecha' => $date->toDateString(),
        'alcance' => 'universitario',
    ]);

    $queued = session('filament.notifications', []);

    expect($queued)->toHaveCount(1);
    expect($queued[0]['title'])->toBe('Feriado registrado: Feriado de Prueba UMSS');
    expect($queued[0]['body'])->toContain('1 clase(s)');
    expect($queued[0]['body'])->toContain($date->format('d/m/Y'));
    expect($queued[0]['status'])->toBe('warning');
});

it('does not warn when the affected sessions belong only to closed groups', function () {
    $date = Carbon::today()->next(Carbon::WEDNESDAY);

    $group = Group::factory()->cerrado()->create();
    Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => $date->toDateString(),
    ]);

    Holiday::create([
        'nombre' => 'Feriado sin grupos activos',
        'fecha' => $date->toDateString(),
        'alcance' => 'departamental',
    ]);

    expect(session('filament.notifications', []))->toBeEmpty();
});

it('rejects postponing a session when another session of the same group overlaps the target date', function () {
    $group = Group::factory()->create();
    $targetDate = Carbon::today()->next(Carbon::MONDAY);

    Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => $targetDate->toDateString(),
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    $session = Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => Carbon::today()->addDays(2)->toDateString(),
        'hora_inicio' => '09:00',
        'hora_fin' => '11:00',
    ]);

    $service = app(HolidayService::class);

    expect(fn () => $service->postponeSession($session, $targetDate, 'Cruce de horario'))
        ->toThrow(
            ValidationException::class,
            'La nueva fecha coincide con otra clase ya programada para este grupo.'
        );
});

it('rejects postponing a session when the instructor already has another class in that slot', function () {
    Role::firstOrCreate(['name' => UserRole::INSTRUCTOR->value]);
    $instructor = User::factory()->create();
    $instructor->assignRole(UserRole::INSTRUCTOR->value);
    $targetDate = Carbon::today()->next(Carbon::TUESDAY);

    $otherCourse = Course::factory()->create(['instructor_id' => $instructor->id]);
    $otherGroup = Group::factory()->create(['course_id' => $otherCourse->id]);
    Session::factory()->create([
        'group_id' => $otherGroup->id,
        'fecha' => $targetDate->toDateString(),
        'hora_inicio' => '14:00',
        'hora_fin' => '16:00',
    ]);

    $ownCourse = Course::factory()->create(['instructor_id' => $instructor->id]);
    $ownGroup = Group::factory()->create(['course_id' => $ownCourse->id]);
    $session = Session::factory()->create([
        'group_id' => $ownGroup->id,
        'fecha' => Carbon::today()->addDays(2)->toDateString(),
        'hora_inicio' => '15:00',
        'hora_fin' => '17:00',
    ]);

    $service = app(HolidayService::class);

    expect(fn () => $service->postponeSession($session, $targetDate, 'Cruce de horario'))
        ->toThrow(
            ValidationException::class,
            'Ya tienes otra clase programada en ese horario para la misma fecha.'
        );
});

it('allows postponing when the target day has no overlapping classes for the group or instructor', function () {
    $group = Group::factory()->create();
    $targetDate = Carbon::today()->next(Carbon::WEDNESDAY);

    Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => $targetDate->toDateString(),
        'hora_inicio' => '08:00',
        'hora_fin' => '10:00',
    ]);

    $session = Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => Carbon::today()->addDays(2)->toDateString(),
        'hora_inicio' => '14:00',
        'hora_fin' => '16:00',
    ]);

    $service = app(HolidayService::class);
    $result = $service->postponeSession($session, $targetDate, 'Cambio de horario sin choque');

    expect($result->es_pospuesta)->toBeTrue();
    expect($result->fecha->toDateString())->toBe($targetDate->toDateString());
});
