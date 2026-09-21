<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EvaluationCriteriaResource\Pages;
use App\Models\EvaluationCriteria;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EvaluationCriteriaResource extends Resource
{
    protected static ?string $model = EvaluationCriteria::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Evaluación & Certificados';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Criterio de Evaluación';

    protected static ?string $modelLabelPlural = 'Criterios de Evaluación';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'nombre')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ponderacion')
                    ->numeric()
                    ->suffix('%')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ponderacion')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime()
                    ->sortable(),
            ])
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
            'index' => Pages\ListEvaluationCriterias::route('/'),
            'create' => Pages\CreateEvaluationCriteria::route('/create'),
            'view' => Pages\ViewEvaluationCriteria::route('/{record}'),
            'edit' => Pages\EditEvaluationCriteria::route('/{record}/edit'),
        ];
    }
}
