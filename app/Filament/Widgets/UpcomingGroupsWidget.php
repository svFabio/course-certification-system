<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Group;
use App\Support\BusinessRules;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingGroupsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Monitoreo de Grupos Próximos — Semáforo de Cupos';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Group::query()
                    ->with(['course'])
                    ->withCount([
                        'preinscriptions as confirmed_count' => fn (Builder $q) => $q->where('status', PreinscriptionStatus::INSCRITO),
                        'preinscriptions as pending_count' => fn (Builder $q) => $q->where('status', PreinscriptionStatus::PENDIENTE_PAGO),
                    ])
                    ->whereIn('status', [GroupStatus::NO_HABILITADO, GroupStatus::HABILITADO, GroupStatus::COMPLETO])
                    ->latest('created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('course.nombre')
                    ->label('Curso')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Grupo')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('confirmed_count')
                    ->label('Inscritos')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state, Group $record) => "{$state} / {$record->cupo_maximo}"),

                Tables\Columns\TextColumn::make('pending_count')
                    ->label('Pend. Pago')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => (int) $state > 0 ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado Grupo')
                    ->badge(),

                Tables\Columns\TextColumn::make('semaforo')
                    ->label('Semáforo de Cupo')
                    ->badge()
                    ->state(function (Group $record): string {
                        $confirmed = (int) $record->confirmed_count;
                        $min = $record->cupo_minimo ?? BusinessRules::MIN_GROUP_CAPACITY;
                        $max = $record->cupo_maximo;

                        if ($confirmed >= $max) {
                            return 'Cupo Lleno';
                        }

                        if ($confirmed >= $min) {
                            $disponibles = $max - $confirmed;

                            return "Habilitado ({$disponibles} disponibles)";
                        }

                        $faltantes = $min - $confirmed;

                        return "Faltan {$faltantes} para habilitar";
                    })
                    ->color(function (Group $record): string {
                        $confirmed = (int) $record->confirmed_count;
                        $min = $record->cupo_minimo ?? BusinessRules::MIN_GROUP_CAPACITY;
                        $max = $record->cupo_maximo;

                        if ($confirmed >= $max) {
                            return 'danger';
                        }

                        if ($confirmed >= $min) {
                            return 'success';
                        }

                        return 'warning';
                    })
                    ->icon(function (Group $record): string {
                        $confirmed = (int) $record->confirmed_count;
                        $min = $record->cupo_minimo ?? BusinessRules::MIN_GROUP_CAPACITY;

                        return $confirmed >= $min ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle';
                    }),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
