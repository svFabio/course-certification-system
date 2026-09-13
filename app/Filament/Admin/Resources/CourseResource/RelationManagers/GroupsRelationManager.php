<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CourseResource\RelationManagers;

use App\Enums\GroupStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class GroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'groups';

    protected static ?string $title = 'Grupos';

    public function form(Form $form): Form
    {
        return $form->schema([
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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('hora_inicio')
                    ->time(),
                Tables\Columns\TextColumn::make('hora_fin')
                    ->time(),
                Tables\Columns\TextColumn::make('cupo_maximo'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
