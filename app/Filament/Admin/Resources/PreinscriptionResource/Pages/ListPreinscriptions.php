<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PreinscriptionResource\Pages;

use App\Enums\PreinscriptionStatus;
use App\Filament\Admin\Resources\PreinscriptionResource;
use App\Models\Preinscription;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPreinscriptions extends ListRecords
{
    protected static string $resource = PreinscriptionResource::class;

    public function getTabs(): array
    {
        return [
            'pendientes' => Tab::make('Preinscripciones (Pendientes)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PreinscriptionStatus::PENDIENTE_PAGO))
                ->badge(fn () => Preinscription::where('status', PreinscriptionStatus::PENDIENTE_PAGO)->count())
                ->badgeColor('warning'),
            'inscritos' => Tab::make('Inscritos (Pagados)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PreinscriptionStatus::INSCRITO))
                ->badge(fn () => Preinscription::where('status', PreinscriptionStatus::INSCRITO)->count())
                ->badgeColor('success'),
            'todos' => Tab::make('Todos')
                ->badge(fn () => Preinscription::count()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'pendientes';
    }
}
