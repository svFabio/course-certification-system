<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Events\GroupSheetsNeedRefresh;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('dispatches a sheets refresh event when attendance is created', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $group = Group::factory()->create();
    $session = Session::factory()->create(['group_id' => $group->id]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    $session->attendances()->create([
        'preinscription_id' => $preinscription->id,
        'status' => AttendanceStatus::PRESENTE,
    ]);

    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $group->id);
});

it('dispatches a sheets refresh event when attendance is updated', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $group = Group::factory()->create();
    $session = Session::factory()->create(['group_id' => $group->id]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);
    $attendance = $session->attendances()->create([
        'preinscription_id' => $preinscription->id,
        'status' => AttendanceStatus::AUSENTE,
    ]);

    Event::fake([GroupSheetsNeedRefresh::class]);

    $attendance->update(['status' => AttendanceStatus::PRESENTE]);

    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $group->id);
});

it('dispatches a sheets refresh event when attendance is deleted', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $group = Group::factory()->create();
    $session = Session::factory()->create(['group_id' => $group->id]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);
    $attendance = $session->attendances()->create([
        'preinscription_id' => $preinscription->id,
        'status' => AttendanceStatus::PRESENTE,
    ]);

    $attendance->delete();

    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $group->id);
});
