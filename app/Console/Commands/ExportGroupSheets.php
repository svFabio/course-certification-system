<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ExportType;
use App\Enums\PreinscriptionStatus;
use App\Models\Group;
use App\Services\GoogleSheetsService;
use App\Services\SheetExportService;
use Illuminate\Console\Command;

class ExportGroupSheets extends Command
{
    protected $signature = 'sheets:export-groups {--group= : ID de un grupo específico (opcional)} {--type=both : cash|teacher|both}';

    protected $description = 'Exporta las planillas de caja y docente de los grupos con preinscripciones activas a Google Sheets';

    public function handle(): int
    {
        try {
            $type = ExportType::from($this->option('type'));
        } catch (\ValueError) {
            $this->error('Tipo de exportación inválido: '.$this->option('type').'. Valores permitidos: cash, teacher, both.');

            return self::FAILURE;
        }

        if (! GoogleSheetsService::isConfigured()) {
            $this->error('Google Sheets no configurado. Configure las credenciales en Admin > Configuración > Google Sheets.');

            return self::FAILURE;
        }

        $service = app(SheetExportService::class);

        $groupId = $this->option('group');

        if ($groupId !== null && $groupId !== '') {
            $group = Group::find((int) $groupId);

            if (! $group) {
                $this->error("No se encontró el grupo con ID {$groupId}.");

                return self::FAILURE;
            }

            $groups = collect([$group]);
        } else {
            $groups = Group::with('course')
                ->whereHas('preinscriptions', fn ($q) => $q->whereIn('status', [
                    PreinscriptionStatus::PENDIENTE_PAGO,
                    PreinscriptionStatus::INSCRITO,
                ]))
                ->orderBy('id')
                ->get();
        }

        if ($groups->isEmpty()) {
            $this->info('No hay grupos con preinscripciones activas para exportar.');

            return self::SUCCESS;
        }

        $exported = [];
        $failures = 0;

        foreach ($groups as $group) {
            try {
                $tabs = $service->exportGroup($group, $type);
                $this->info('Grupo '.$group->id.' ('.$group->nombre.'): exportadas '.implode(', ', $tabs));
                $exported[] = $group;
            } catch (\Exception $e) {
                $this->error('Grupo '.$group->id.' ('.$group->nombre.'): '.$e->getMessage());
                $failures++;
            }
        }

        if ($failures > 0) {
            $this->error('Finalizado con '.$failures.' grupo(s) con errores.');

            return self::FAILURE;
        }

        $this->info('Exportación completada para '.count($exported).' grupo(s).');

        return self::SUCCESS;
    }
}
