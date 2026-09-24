<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SessionResource\Pages;
use App\Models\Session;
use App\Services\HolidayService;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SessionResource extends Resource
{
    protected static ?string $model = Session::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Gestión Académica';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Sesión';

    protected static ?string $pluralModelLabel = 'Sesiones';

    protected static ?string $navigationLabel = 'Sesiones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('group_id')
                    ->relationship('group', 'nombre')
                    ->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('fecha')
                    ->required(),
                Forms\Components\TimePicker::make('hora_inicio')
                    ->required(),
                Forms\Components\TimePicker::make('hora_fin')
                    ->required(),
                Forms\Components\Toggle::make('dictada')
                    ->default(false),
                Forms\Components\Textarea::make('motivo_reprogramacion')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group.nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hora_inicio')
                    ->time(),
                Tables\Columns\TextColumn::make('hora_fin')
                    ->time(),
                Tables\Columns\IconColumn::make('dictada')
                    ->label('Dictada')
                    ->boolean(),
                Tables\Columns\IconColumn::make('es_pospuesta')
                    ->label('Reprogramada')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('postpone')
                    ->label('Reprogramar / Posponer')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\DatePicker::make('nueva_fecha')
                            ->label('Nueva fecha para la clase')
                            ->default(fn (Session $record) => $record->fecha->addDays(1))
                            ->required()
                            ->afterOrEqual(today())
                            ->rules([
                                function (string $attribute, mixed $value, Closure $fail): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $date = Carbon::parse($value);

                                    if ($date->isWeekend() || app(HolidayService::class)->isHoliday($date)) {
                                        $fail('La nueva fecha no puede caer en feriado ni en fin de semana.');
                                    }
                                },
                            ]),
                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo de reprogramación')
                            ->placeholder('Ej. Feriado sobrevenido, duelo institucional, emergencia del instructor...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Session $record, array $data) {
                        $holidayService = app(HolidayService::class);
                        $holidayService->postponeSession(
                            $record,
                            Carbon::parse($data['nueva_fecha']),
                            $data['motivo']
                        );

                        Notification::make()
                            ->title('Clase reprogramada correctamente.')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListSessions::route('/'),
            'create' => Pages\CreateSession::route('/create'),
            'view' => Pages\ViewSession::route('/{record}'),
            'edit' => Pages\EditSession::route('/{record}/edit'),
        ];
    }
}
