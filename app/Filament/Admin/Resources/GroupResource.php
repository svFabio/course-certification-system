<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\GroupStatus;
use App\Filament\Admin\Resources\GroupResource\Pages;
use App\Models\Group;
use App\Support\BusinessRules;
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
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'nombre')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TimePicker::make('hora_inicio')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                        $courseId = $get('course_id');
                        if ($courseId) {
                            $course = \App\Models\Course::find($courseId);
                            if ($course) {
                                $duration = \App\Support\BusinessRules::SESSION_DURATION_HOURS[$course->carga_horaria] ?? 1.5;
                                $inicio = \Carbon\Carbon::parse($state);
                                $set('hora_fin', $inicio->copy()->addMinutes((int) ($duration * 60))->format('H:i'));
                            }
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course.nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hora_inicio')
                    ->time(),
                Tables\Columns\TextColumn::make('hora_fin')
                    ->time(),
                Tables\Columns\TextColumn::make('cupo_maximo'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(fn (GroupStatus $state) => match ($state) {
                        GroupStatus::HABILITADO => 'success',
                        GroupStatus::NO_HABILITADO => 'warning',
                        GroupStatus::COMPLETO => 'info',
                        GroupStatus::EN_CURSO => 'primary',
                        GroupStatus::FINALIZADO => 'gray',
                        GroupStatus::CERRADO => 'danger',
                    }),
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
