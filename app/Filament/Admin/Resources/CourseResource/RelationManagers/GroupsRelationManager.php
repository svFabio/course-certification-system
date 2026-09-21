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
                ->label('Nombre del grupo')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('aula')
                ->label('Aula / Laboratorio')
                ->placeholder('Ej. Laboratorio 1')
                ->maxLength(100),
            Forms\Components\TimePicker::make('hora_inicio')
                ->label('Hora de inicio (Formatos UMSS: 06:45, 08:15, 09:45, 11:15, 14:15, 15:45, 17:15, 18:45)')
                ->datalist(array_keys(BusinessRules::UMSS_SCHEDULE_BLOCKS))
                ->required()
                ->live()
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
                ->required()
                ->rules(function ($get) {
                    return function ($attribute, $value, $fail) use ($get) {
                        $min = (int) $get('cupo_minimo');
                        if ($min > 0 && (int) $value < $min) {
                            $fail("El cupo máximo debe ser mayor o igual al cupo mínimo ({$min}).");
                        }
                    };
                }),
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
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('aula')
                    ->label('Aula')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('hora_inicio')
                    ->label('Hora inicio')
                    ->time(),
                Tables\Columns\TextColumn::make('hora_fin')
                    ->label('Hora fin')
                    ->time(),
                Tables\Columns\TextColumn::make('cupo_maximo')
                    ->label('Cupo máximo'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
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
