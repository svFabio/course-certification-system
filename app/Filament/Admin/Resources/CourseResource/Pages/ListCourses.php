<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Exports\CoursesGeneralExport;
use App\Exports\FinancialReportExport;
use App\Filament\Admin\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('exportarCatalogoExcel')
                ->label('Exportar Cursos (Excel)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new CoursesGeneralExport,
                    'catalogo-cursos-'.now()->format('Y-m-d').'.xlsx'
                )),
            Actions\Action::make('reporteFinancieroGeneral')
                ->label('Consolidado Caja (Excel)')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->action(fn () => Excel::download(
                    new FinancialReportExport,
                    'consolidado-financiero-'.now()->format('Y-m-d').'.xlsx'
                )),
        ];
    }
}
