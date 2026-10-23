<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CourseResource\RelationManagers;

use App\Enums\GroupStatus;
use App\Filament\Admin\Resources\GroupResource;
use App\Models\Group;
use App\Support\BusinessRules;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class GroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'groups';

    protected static ?string $title = 'Grupos de este curso';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre del grupo')
                ->required()
                ->maxLength(255)
                ->rules([
                    fn (?Group $record, RelationManager $livewire): Unique => Rule::unique('groups', 'nombre')
                        ->where('course_id', $livewire->getOwnerRecord()->id)
                        ->ignore($record?->id),
                ])
                ->validationMessages([
                    'unique' => 'Ya existe un grupo con este nombre en este curso.',
                ]),
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
                    if ($course && $state) {
                        $set('hora_fin', BusinessRules::calculateSessionEndTime($state, $course->carga_horaria)->format('H:i'));
                    }
                }),
            Forms\Components\TimePicker::make('hora_fin')
                ->label('Hora de fin (calculada automáticamente)')
                ->disabled()
                ->helperText('Se calcula automáticamente según la carga horaria del curso.'),
            Forms\Components\TextInput::make('cupo_minimo')
                ->numeric()
                ->required()
                ->minValue(BusinessRules::MIN_GROUP_CAPACITY)
                ->default(BusinessRules::MIN_GROUP_CAPACITY),
            Forms\Components\TextInput::make('cupo_maximo')
                ->numeric()
                ->required()
                ->rules(fn (Forms\Get $get): array => [
                    BusinessRules::validateMaxGroupCapacity($get('cupo_minimo')),
                ]),
            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options(GroupStatus::class)
                ->default(GroupStatus::NO_HABILITADO)
                ->hiddenOn('create'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Group $record): string => GroupResource::getUrl('view', ['record' => $record]))
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
                Tables\Actions\Action::make('verGrupo')
                    ->label('Ver Grupo')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Group $record): string => GroupResource::getUrl('view', ['record' => $record])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }
}
