<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Widgets;

use App\Models\Session;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingSessionsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Próximas sesiones (14 días)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Session::query()
                    ->whereHas('group.course', fn (Builder $q) => $q->where('instructor_id', auth()->id()))
                    ->where('fecha', '>=', today())
                    ->where('fecha', '<=', today()->addDays(14))
                    ->where('dictada', false)
                    ->with(['group:id,nombre,course_id', 'group.course:id,nombre'])
                    ->orderBy('fecha')
                    ->orderBy('hora_inicio')
            )
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hora_inicio')
                    ->label('Horario')
                    ->formatStateUsing(fn (Session $record): string => $record->hora_inicio->format('H:i').' – '.$record->hora_fin->format('H:i')),

                Tables\Columns\TextColumn::make('group.course.nombre')
                    ->label('Curso'),

                Tables\Columns\TextColumn::make('group.nombre')
                    ->label('Grupo')
                    ->badge()
                    ->color('gray'),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
