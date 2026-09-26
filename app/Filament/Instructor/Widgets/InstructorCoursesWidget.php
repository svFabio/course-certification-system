<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Widgets;

use App\Enums\PreinscriptionStatus;
use App\Models\Group;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class InstructorCoursesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Mis cursos';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Group::query()
                    ->whereHas('course', fn (Builder $q) => $q->where('instructor_id', auth()->id()))
                    ->with(['course:id,nombre'])
                    ->withCount([
                        'preinscriptions as inscribed_count' => fn (Builder $q) => $q->whereIn('status', [
                            PreinscriptionStatus::INSCRITO->value,
                            PreinscriptionStatus::PENDIENTE_PAGO->value,
                        ]),
                        'sessions as sessions_count',
                        'sessions as dictada_sessions_count' => fn (Builder $q) => $q->where('dictada', true),
                    ])
                    ->orderBy('course_id')
                    ->orderBy('id')
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

                Tables\Columns\TextColumn::make('inscribed_count')
                    ->label('Inscritos')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sessions_count')
                    ->label('Sesiones avanzadas')
                    ->formatStateUsing(fn (Group $record): string => "{$record->dictada_sessions_count}/{$record->sessions_count}"),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
