<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources;

use App\Filament\Instructor\Resources\InstructorCourseResource\Pages;
use App\Models\Course;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InstructorCourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Mis Cursos';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Curso';

    protected static ?string $modelLabelPlural = 'Cursos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Read-only view — no form fields needed
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('carga_horaria')
                    ->badge(),
                Tables\Columns\TextColumn::make('nivel'),
                Tables\Columns\TextColumn::make('periodo'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime()
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->where('instructor_id', auth()->id()))
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructorCourses::route('/'),
            'view' => Pages\ViewInstructorCourse::route('/{record}'),
        ];
    }
}
