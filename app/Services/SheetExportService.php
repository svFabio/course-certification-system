<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExportType;
use App\Models\Group;
use App\Models\SystemSetting;

class SheetExportService
{
    public function __construct(
        private GoogleSheetsService $sheets,
    ) {}

    /**
     * Export the requested planillas for a group. Creates tabs if missing and
     * overwrites them from A1 with the current DB state.
     *
     * @return list<string> names of exported tabs
     *
     * @throws \RuntimeException when Google Sheets is not configured
     */
    public function exportGroup(Group $group, ExportType $exportType = ExportType::BOTH): array
    {
        if (! GoogleSheetsService::isConfigured()) {
            throw new \RuntimeException('Google Sheets no configurado. Configure las credenciales en Admin > Configuración > Google Sheets.');
        }

        $this->sheets->setSpreadsheetId(SystemSetting::get('google_sheets_spreadsheet_id'));

        $builder = new SheetExportBuilder($group);
        $tabs = [];

        if ($exportType === ExportType::CASH || $exportType === ExportType::BOTH) {
            $tab = $builder->getCashSheetTabName();
            if (! $this->sheets->checkTabExists($tab)) {
                $this->sheets->createTab($tab);
            }
            $this->sheets->writeCells($tab.'!A1', $builder->buildCashSheetRows());
            $tabs[] = $tab;
        }

        if ($exportType === ExportType::TEACHER || $exportType === ExportType::BOTH) {
            $tab = $builder->getTeacherSheetTabName();
            if (! $this->sheets->checkTabExists($tab)) {
                $this->sheets->createTab($tab);
            }
            $this->sheets->writeCells($tab.'!A1', $builder->buildTeacherSheetRows());
            $tabs[] = $tab;
        }

        return $tabs;
    }
}
