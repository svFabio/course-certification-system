<?php
declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GroupResource\Pages;
use App\Models\Group;
use App\Enums\GroupStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Gestión Académica';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Grupo';
    protected static ?string $modelLabelPlural = 'Grupos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // TODO: Add form fields for nombre, hora_inicio, hora_fin, cupo_minimo, cupo_maximo, status
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TimePicker::make('hora_inicio')
                    ->required(),
                Forms\Components\TimePicker::make('hora_fin')
                    ->required(),
                Forms\Components\TextInput::make('cupo_minimo')
                    ->numeric()
                    ->default(15),
                Forms\Components\TextInput::make('cupo_maximo')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(GroupStatus::class)
                    ->default(GroupStatus::HABILITADO),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // TODO: Add table columns
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hora_inicio')
                    ->time(),
                Tables\Columns\TextColumn::make('hora_fin')
                    ->time(),
                Tables\Columns\TextColumn::make('cupo_maximo'),
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
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'view' => Pages\ViewGroup::route('/{record}'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }
}
