<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HolidayResource\Pages;
use App\Models\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Gestión Académica';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Feriado / Asueto';

    protected static ?string $modelLabelPlural = 'Feriados y Asuetos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre de la festividad / efeméride')
                    ->placeholder('Ej. Aniversario Cívico de Cochabamba, Todos Santos...')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('fecha')
                    ->label('Fecha del feriado')
                    ->required(),
                Forms\Components\Select::make('alcance')
                    ->label('Alcance jurisdiccional')
                    ->options([
                        'nacional' => 'Nacional (Bolivia)',
                        'departamental' => 'Departamental (Cochabamba)',
                        'universitario' => 'Universitario (UMSS)',
                    ])
                    ->default('departamental')
                    ->required(),
                Forms\Components\Toggle::make('activo')
                    ->label('Feriado activo')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('alcance')
                    ->badge()
                    ->colors([
                        'primary' => 'universitario',
                        'success' => 'departamental',
                        'warning' => 'nacional',
                    ]),
                Tables\Columns\IconColumn::make('activo')
                    ->boolean(),
            ])
            ->defaultSort('fecha', 'asc')
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
            'index' => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit' => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}
