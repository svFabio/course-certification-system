<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GroupResource\RelationManagers;

use App\Models\Session;
use App\Services\HolidayService;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'Sesiones de Clase';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('fecha')
                ->label('Fecha de la clase')
                ->required()
                ->rules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        if (blank($value)) {
                            return;
                        }

                        if (app(HolidayService::class)->isHoliday(Carbon::parse($value))) {
                            $fail('No se puede programar una clase en una fecha considerada feriado.');
                        }
                    },
                ]),
            Forms\Components\TimePicker::make('hora_inicio')
                ->label('Hora de inicio')
                ->required(),
            Forms\Components\TimePicker::make('hora_fin')
                ->label('Hora de fin')
                ->required()
                ->rules([
                    fn (Forms\Get $get, ?Session $record, RelationManager $livewire): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record, $livewire): void {
                        $groupId = $livewire->getOwnerRecord()->id;
                        $fecha = $get('fecha');
                        $horaInicio = $get('hora_inicio');

                        if (blank($groupId) || blank($fecha) || blank($horaInicio) || blank($value)) {
                            return;
                        }

                        $clashExists = Session::query()
                            ->where('group_id', $groupId)
                            ->whereDate('fecha', Carbon::parse($fecha)->toDateString())
                            ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                            ->where('hora_inicio', '<', $value)
                            ->where('hora_fin', '>', $horaInicio)
                            ->exists();

                        if ($clashExists) {
                            $fail('El horario se cruza con otra sesión ya programada para este grupo.');
                        }
                    },
                ]),
            Forms\Components\Toggle::make('dictada')
                ->label('Clase dictada')
                ->inline(false),
            Forms\Components\Toggle::make('es_pospuesta')
                ->label('Reprogramada')
                ->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('fecha')
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('hora_inicio')
                    ->label('Inicio')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('hora_fin')
                    ->label('Fin')
                    ->time('H:i'),
                Tables\Columns\IconColumn::make('dictada')
                    ->label('Dictada')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\IconColumn::make('es_pospuesta')
                    ->label('Reprogramada')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),
            ])
            ->defaultSort('fecha', 'asc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->successNotification(
                        Notification::make()
                            ->title('Sesión actualizada correctamente')
                            ->success()
                    ),
            ]);
    }
}
