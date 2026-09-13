<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PreinscriptionResource\Pages;
use App\Models\Preinscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PreinscriptionResource extends Resource
{
    protected static ?string $model = Preinscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Inscripciones';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Preinscripción';

    protected static ?string $modelLabelPlural = 'Preinscripciones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('group_id')
                    ->relationship('group', 'nombre')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('ci')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('nombres')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('apellido_paterno')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('apellido_materno')
                    ->maxLength(255),
                Forms\Components\TextInput::make('celular')
                    ->tel()
                    ->maxLength(20),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('tipo_participante')
                    ->options([
                        'umss' => 'UMSS',
                        'externo' => 'Externo',
                        'auxiliar' => 'Auxiliar',
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'confirmada' => 'Confirmada',
                        'cancelada' => 'Cancelada',
                    ])
                    ->default('pendiente'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ci')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombres')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apellido_paterno'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_participante')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
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
            'index' => Pages\ListPreinscriptions::route('/'),
            'create' => Pages\CreatePreinscription::route('/create'),
            'view' => Pages\ViewPreinscription::route('/{record}'),
            'edit' => Pages\EditPreinscription::route('/{record}/edit'),
        ];
    }
}
