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

    /**
     * Committed seats drive capacity decisions everywhere else in the domain
     * (AGENTS.md §2): the auto-enablement observer, PreinscriptionService and
     * Group::getCuposOcupadosAttribute all count PENDIENTE_PAGO + INSCRITO.
     * The semaphore must use the same rule, otherwise it can ask the admin to
     * enable a group the system already enabled.
     */
    public function committedSeats(Group $group): int
    {
        return (int) $group->confirmed_count + (int) $group->pending_count;
    }

    public function semaphoreState(Group $group): string
    {
        $committed = $this->committedSeats($group);
        $min = $group->cupo_minimo ?? BusinessRules::MIN_GROUP_CAPACITY;
        $max = $group->cupo_maximo;

        if ($committed >= $max) {
            return 'Cupo Lleno';
        }

        if ($committed >= $min) {
            $disponibles = $max - $committed;

            return "Habilitado ({$disponibles} disponibles)";
        }

        $faltantes = $min - $committed;

        return "Faltan {$faltantes} para habilitar";
    }

    public function semaphoreColor(Group $group): string
    {
        $committed = $this->committedSeats($group);
        $min = $group->cupo_minimo ?? BusinessRules::MIN_GROUP_CAPACITY;
        $max = $group->cupo_maximo;

        if ($committed >= $max) {
            return 'danger';
        }

        if ($committed >= $min) {
            return 'success';
        }

        return 'warning';
    }

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
                    ->state(fn (Group $record): string => $this->semaphoreState($record))
                    ->color(fn (Group $record): string => $this->semaphoreColor($record))
                    ->icon(function (Group $record): string {
                        $committed = $this->committedSeats($record);
                        $min = $record->cupo_minimo ?? BusinessRules::MIN_GROUP_CAPACITY;

                        return $committed >= $min ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle';
                    }),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
