<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GroupResource\Pages;

use App\Enums\PreinscriptionStatus;
use App\Filament\Admin\Resources\GroupResource;
use App\Services\CourseDemandService;
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
            Actions\CreateAction::make(),
        ];
    }
}
