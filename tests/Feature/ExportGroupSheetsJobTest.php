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
use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Concerns\ConfiguresSheets;

uses(ConfiguresSheets::class);

$originalCacheConfig = null;

beforeEach(function () use (&$originalCacheConfig): void {
    $originalCacheConfig = [
        'default' => config('cache.default'),
        'connection' => config('cache.stores.database.connection'),
        'lock_connection' => config('cache.stores.database.lock_connection'),
    ];
});

afterEach(function () use (&$originalCacheConfig): void {
    config([
        'cache.default' => $originalCacheConfig['default'],
        'cache.stores.database.connection' => $originalCacheConfig['connection'],
        'cache.stores.database.lock_connection' => $originalCacheConfig['lock_connection'],
    ]);

    app()->forgetInstance(Repository::class);
});

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
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id, 'fotocopia_ci' => true]);

    $payment = Payment::factory()->pending()->create(['preinscription_id' => $preinscription->id]);

    $this->actingAs($user);

    app(PaymentService::class)->verify($payment);

    Event::assertDispatched(GroupSheetsNeedRefresh::class, fn ($event) => $event->groupId === $group->id);
});

it('does not crash or double queue when the unique lock is held and the event is dispatched inside a transaction', function () {
    Queue::fake();

    // The unique job lock must run on its own connection so it is not part of the
    // wrapping test transaction: after the fix the lock runs post-commit, exactly
    // like production, where no transaction is open at that point.
    $cacheConnection = 'pgsql_cache';
    config([
        'database.connections.'.$cacheConnection => config('database.connections.'.config('database.default')),
        'cache.stores.database.connection' => $cacheConnection,
        'cache.stores.database.lock_connection' => $cacheConnection,
        'cache.default' => 'database',
    ]);

    $cacheRepository = Cache::store('database');
    app()->instance(Repository::class, $cacheRepository);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    $lockKey = $cacheRepository->getStore()->getPrefix().UniqueLock::getKey(new ExportGroupSheetsJob($group->id));

    DB::connection($cacheConnection)->table('cache_locks')->insert([
        'key' => $lockKey,
        'owner' => 'someone-else',
        'expiration' => now()->addSeconds(600)->getTimestamp(),
    ]);

    $lockQueries = 0;
    DB::connection($cacheConnection)->listen(function () use (&$lockQueries): void {
        $lockQueries++;
    });

    DB::transaction(function () use ($group, &$lockQueries): void {
        GroupSheetsNeedRefresh::dispatch($group->id);

        expect($lockQueries)->toBe(0);
    });

    Queue::assertNotPushed(ExportGroupSheetsJob::class);
    expect($lockQueries)->toBeGreaterThan(0);
});

it('defers the export job dispatch until after the database transaction commits', function () {
    Queue::fake();

    config(['cache.default' => 'database']);
    $cacheRepository = Cache::store('database');
    app()->instance(Repository::class, $cacheRepository);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    DB::transaction(function () use ($group): void {
        GroupSheetsNeedRefresh::dispatch($group->id);

        Queue::assertNothingPushed();
    });

    Queue::assertPushed(ExportGroupSheetsJob::class, fn ($job) => $job->groupId === $group->id && $job->delay !== null);
});
