<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\GroupSheetsNeedRefresh;
use App\Jobs\ExportGroupSheetsJob;

class QueueGroupSheetsExport
{
    public function handle(GroupSheetsNeedRefresh $event): void
    {
        ExportGroupSheetsJob::dispatch($event->groupId)
            ->delay(now()->addSeconds((int) config('services.google.sheets.export_debounce_seconds', 60)));
    }
}
