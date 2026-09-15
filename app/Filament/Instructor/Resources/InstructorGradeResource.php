<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources;

use App\Filament\Instructor\Resources\InstructorGradeResource\Pages;
use App\Models\Grade;
use App\Support\BusinessRules;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InstructorGradeResource extends Resource
{
    protected static ?string $model = Grade::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Mis Cursos';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Calificación';

    protected static ?string $modelLabelPlural = 'Calificaciones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('evaluation_criteria_id')
                    ->relationship('evaluationCriteria', 'nombre')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('preinscription_id')
                    ->relationship('preinscription', 'nombres')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('nota')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(BusinessRules::GRADE_MAX)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('evaluationCriteria.nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('preinscription.nombres')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nota')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->whereHas('evaluationCriteria.course', fn ($q) => $q->where('instructor_id', auth()->id())))
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructorGrades::route('/'),
            'create' => Pages\CreateInstructorGrade::route('/create'),
            'view' => Pages\ViewInstructorGrade::route('/{record}'),
            'edit' => Pages\EditInstructorGrade::route('/{record}/edit'),
        ];
    }
}
