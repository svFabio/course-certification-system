<?php

declare(strict_types=1);

use App\Events\GroupSheetsNeedRefresh;
use App\Models\Course;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('dispatches a sheets refresh per group when the course name changes', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $course = Course::factory()->create();
    $firstGroup = Group::factory()->create(['course_id' => $course->id]);
    $secondGroup = Group::factory()->create(['course_id' => $course->id]);

    $course->update(['nombre' => 'Curso Renombrado']);

    Event::assertDispatchedTimes(GroupSheetsNeedRefresh::class, 2);
    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $firstGroup->id);
    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $secondGroup->id);
});

it('does not refresh when unrelated course content changes', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $course = Course::factory()->create();
    Group::factory()->create(['course_id' => $course->id]);

    $course->update(['contenido' => 'Otro contenido.']);

    Event::assertNotDispatched(GroupSheetsNeedRefresh::class);
});

it('dispatches a sheets refresh when a group schedule changes', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $course = Course::factory()->create(['carga_horaria' => '20']);
    $group = Group::factory()->create(['course_id' => $course->id, 'hora_inicio' => '08:00']);

    $group->update(['hora_inicio' => '09:00']);

    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $group->id);
});
