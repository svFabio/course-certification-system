<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\ExportType;
use App\Events\GroupSheetsNeedRefresh;
use App\Jobs\ExportGroupSheetsJob;
use App\Listeners\QueueGroupSheetsExport;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\PaymentService;
use App\Services\SheetExportService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Concerns\ConfiguresSheets;

uses(ConfiguresSheets::class);

it('queues a debounced export when the refresh event fires', function () {
    Queue::fake();

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    app(QueueGroupSheetsExport::class)->handle(new GroupSheetsNeedRefresh($group->id));

    Queue::assertPushed(ExportGroupSheetsJob::class, fn ($job) => $job->groupId === $group->id && $job->delay !== null);
});

it('exports the group with both planillas when configured', function () {
    $this->configureSheets();

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $this->mock(SheetExportService::class)
        ->shouldReceive('exportGroup')
        ->once()
        ->with(
            Mockery::on(fn (Group $exportedGroup) => $exportedGroup instanceof Group && $exportedGroup->id === $group->id),
            ExportType::BOTH
        )
        ->andReturn(['TAB']);

    (new ExportGroupSheetsJob($group->id))->handle();
});

it('silently aborts when group no longer exists', function () {
    (new ExportGroupSheetsJob(999999))->handle();

    expect(true)->toBeTrue();
});

it('silently aborts when not configured', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    $this->mock(SheetExportService::class)
        ->shouldReceive('exportGroup')
        ->never();

    (new ExportGroupSheetsJob($group->id))->handle();

    expect(true)->toBeTrue();
});

it('attendance save dispatches a sheets refresh event for its group', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    app(AttendanceService::class)->registerManual($session, $preinscription->id, AttendanceStatus::PRESENTE->value);

    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $group->id);
});

it('payment verify dispatches a sheets refresh event for its group', function () {
    Event::fake([GroupSheetsNeedRefresh::class]);

    $user = User::factory()->create();
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $payment = Payment::factory()->pending()->create(['preinscription_id' => $preinscription->id]);

    $this->actingAs($user);

    app(PaymentService::class)->verify($payment);

    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $group->id);
});
