<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ExportType;
use App\Models\Group;
use App\Services\GoogleSheetsService;
use App\Services\SheetExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Re-exports the group's planillas to Google Sheets.
 *
 * Queued by the App\Listeners\QueueGroupSheetsExport listener, which applies
 * the debounce so bursts of saves produce a single re-export per group.
 */
class ExportGroupSheetsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public int $uniqueFor = 900;

    public function __construct(public int $groupId) {}

    public function handle(): void
    {
        $group = Group::find($this->groupId);

        if (! $group || ! GoogleSheetsService::isConfigured()) {
            return;
        }

        try {
            app(SheetExportService::class)->exportGroup($group, ExportType::BOTH);
            Log::info("Re-exportación de Google Sheets completada para el grupo {$group->id} ({$group->nombre}).");
        } catch (\Throwable $e) {
            Log::error("Falló la re-exportación de Google Sheets para el grupo {$group->id}: {$e->getMessage()}");

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("Re-exportación de Google Sheets fallida definitivamente para el grupo {$this->groupId}: {$e->getMessage()}");
    }

    public function uniqueId(): string
    {
        return 'export-groups:'.$this->groupId;
    }
}
