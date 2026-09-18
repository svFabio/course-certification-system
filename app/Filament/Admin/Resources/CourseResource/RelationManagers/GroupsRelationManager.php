<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CourseResource\RelationManagers;

use App\Enums\GroupStatus;
use App\Support\BusinessRules;
use Carbon\Carbon;
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
                ->required()
                ->reactive()
                ->afterStateUpdated(function (Forms\Set $set, $state) {
                    $course = $this->getOwnerRecord();
                    if ($course) {
                        $duration = BusinessRules::SESSION_DURATION_HOURS[$course->carga_horaria] ?? 1.5;
                        $inicio = Carbon::parse($state);
                        $set('hora_fin', $inicio->copy()->addMinutes((int) ($duration * 60))->format('H:i'));
                    }
                }),
            Forms\Components\TimePicker::make('hora_fin')
                ->required()
                ->rules(function ($get) {
                    return function ($attribute, $value, $fail) use ($get) {
                        $horaInicio = $get('hora_inicio');
                        if ($horaInicio && $value && $value <= $horaInicio) {
                            $fail('La hora de fin debe ser posterior a la hora de inicio.');
                        }
                    };
                }),
            Forms\Components\TextInput::make('cupo_minimo')
                ->numeric()
                ->default(BusinessRules::MIN_GROUP_CAPACITY),
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
