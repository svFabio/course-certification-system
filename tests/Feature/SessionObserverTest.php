<?php

declare(strict_types=1);

use App\Events\GroupSheetsNeedRefresh;
use App\Models\Group;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('dispatches a sheets refresh event when a session is created', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $group = Group::factory()->create();
    $session = Session::factory()->create(['group_id' => $group->id]);

    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $group->id);
    expect($session->id)->not->toBeNull();
});

it('dispatches a sheets refresh event when a session date or time changes', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $group = Group::factory()->create();
    $session = Session::factory()->create(['group_id' => $group->id]);

    $session->update(['fecha' => today()->addMonth()->toDateString()]);

    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $group->id);
});

it('does not dispatch when only the dictada flag toggles', function () {
    $group = Group::factory()->create();
    $session = Session::factory()->create(['group_id' => $group->id, 'dictada' => false]);

    Event::fake([GroupSheetsNeedRefresh::class]);

    $session->update(['dictada' => true]);

    Event::assertNotDispatched(GroupSheetsNeedRefresh::class);
});

it('dispatches a sheets refresh event when a session is deleted', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $group = Group::factory()->create();
    $session = Session::factory()->create(['group_id' => $group->id]);

    $session->delete();

    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $group->id);
});
