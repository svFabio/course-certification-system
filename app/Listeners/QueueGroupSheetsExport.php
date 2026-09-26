<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\GroupSheetsNeedRefresh;
use App\Jobs\ExportGroupSheetsJob;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class QueueGroupSheetsExport implements ShouldHandleEventsAfterCommit
{
    public function handle(GroupSheetsNeedRefresh $event): void
    {
        ExportGroupSheetsJob::dispatch($event->groupId)
            ->delay(now()->addSeconds((int) config('services.google.sheets.export_debounce_seconds', 60)));
    }
}
