<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\AttendanceStatus;
use App\Filament\Admin\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Gestión Académica';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Asistencia';

    protected static ?string $modelLabelPlural = 'Asistencias';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('session_id')
                    ->relationship('session', 'fecha')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('preinscription_id')
                    ->relationship('preinscription', 'nombres')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(AttendanceStatus::class)
                    ->required(),
                Forms\Components\TextInput::make('lat')
                    ->numeric()
                    ->label('Latitud'),
                Forms\Components\TextInput::make('lng')
                    ->numeric()
                    ->label('Longitud'),
                Forms\Components\TextInput::make('distancia_metros')
                    ->numeric()
                    ->suffix('m'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session.fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('preinscription.nombres')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('distancia_metros')
                    ->suffix('m'),
                Tables\Columns\TextColumn::make('created_at')
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'view' => Pages\ViewAttendance::route('/{record}'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
