<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GroupResource\Pages;

use App\Enums\PreinscriptionStatus;
use App\Filament\Admin\Resources\GroupResource;
use App\Filament\Admin\Widgets\CourseInterestOverviewWidget;
use App\Services\CourseDemandService;
use App\Services\CourseInterestService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListGroups extends ListRecords
{
    protected static string $resource = GroupResource::class;

    public function mount(): void
    {
        parent::mount();

        $courses = app(CourseDemandService::class)->coursesAtCapacity();

        if ($courses->isNotEmpty()) {
            Notification::make()
                ->title('Sobredemanda detectada')
                ->body('Cursos completos: '.$courses->pluck('nombre')->join(', ').'. Considere abrir un grupo adicional. (HU-14)')
                ->warning()
                ->send();
        }
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        if ($query === null) {
            return null;
        }

        return $query->withCount([
            'preinscriptions as confirmados' => fn (Builder $q): Builder => $q->whereIn('status', [
                PreinscriptionStatus::PENDIENTE_PAGO,
                PreinscriptionStatus::INSCRITO,
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('archivarInteresesFinalizados')
                ->label('Archivar intereses de cursos finalizados')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Archivar intereses')
                ->modalDescription('Se descartarán los intereses activos de cursos finalizados o cancelados. Esta acción no crea ni modifica grupos.')
                ->action(function (): void {
                    $archived = app(CourseInterestService::class)->archiveForFinishedCourses();

                    Notification::make()
                        ->title($archived > 0 ? "Se archivaron {$archived} interés(es)." : 'No hay intereses de cursos finalizados para archivar.')
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CourseInterestOverviewWidget::class,
        ];
    }
}
