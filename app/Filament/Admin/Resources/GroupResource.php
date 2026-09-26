<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\ExportType;
use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Events\GroupSheetsNeedRefresh;
use App\Filament\Admin\Resources\GroupResource\Pages;
use App\Mail\GroupMergedNotification;
use App\Models\Course;
use App\Models\Group;
use App\Models\SystemSetting;
use App\Services\GoogleSheetsService;
use App\Services\HolidayService;
use App\Services\SheetExportService;
use App\Support\BusinessRules;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Gestión Académica';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Grupo';

    protected static ?string $pluralModelLabel = 'Grupos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'nombre')
                    ->searchable()
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre del grupo')
                    ->required()
                    ->maxLength(255)
                    ->rules([
                        fn (Forms\Get $get, ?Group $record): Unique => Rule::unique('groups', 'nombre')
                            ->where('course_id', $get('course_id'))
                            ->ignore($record?->id),
                    ])
                    ->validationMessages([
                        'unique' => 'Ya existe un grupo con este nombre para el curso seleccionado.',
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
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                        $courseId = $get('course_id');
                        if ($courseId) {
                            $course = Course::find($courseId);
                            if ($course) {
                                $duration = BusinessRules::SESSION_DURATION_HOURS[$course->carga_horaria] ?? 1.5;
                                $inicio = Carbon::parse($state);
                                $set('hora_fin', $inicio->copy()->addMinutes((int) ($duration * 60))->format('H:i'));
                            }
                        }
                    }),
                Forms\Components\TimePicker::make('hora_fin')
                    ->label('Hora de fin (calculada automáticamente)')
                    ->disabled()
                    ->helperText('Se calcula automáticamente según la carga horaria del curso.'),
                Forms\Components\TextInput::make('cupo_minimo')
                    ->numeric()
                    ->default(BusinessRules::MIN_GROUP_CAPACITY)
                    ->required()
                    ->minValue(1),
                Forms\Components\TextInput::make('cupo_maximo')
                    ->numeric()
                    ->required()
                    ->rules(function (Forms\Get $get) {
                        return function ($attribute, $value, $fail) use ($get) {
                            $min = (int) $get('cupo_minimo');
                            if ($min > 0 && (int) $value < $min) {
                                $fail("El cupo máximo debe ser mayor o igual al cupo mínimo ({$min}).");
                            }
                        };
                    }),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options(GroupStatus::class)
                    ->default(GroupStatus::NO_HABILITADO)
                    ->hiddenOn('create'),
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
                Tables\Columns\TextColumn::make('aula')
                    ->label('Aula')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('hora_inicio')
                    ->time(),
                Tables\Columns\TextColumn::make('hora_fin')
                    ->time(),
                Tables\Columns\TextColumn::make('cupo_maximo')
                    ->label('Cupo máximo'),
                Tables\Columns\TextColumn::make('ocupacion')
                    ->label('Ocupación')
                    ->badge()
                    ->state(fn (Group $record): string => ((int) ($record->confirmados ?? 0)).'/'.$record->cupo_maximo)
                    ->color(function (Group $record): string {
                        $confirmed = (int) ($record->confirmados ?? 0);

                        if ($confirmed >= $record->cupo_maximo) {
                            return 'danger';
                        }

                        return $confirmed < $record->cupo_minimo ? 'warning' : 'success';
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('generateSessions')
                    ->label('Programar 10 Clases')
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->form([
                        Forms\Components\DatePicker::make('fecha_inicio')
                            ->label('Fecha de inicio del curso')
                            ->default(now()->addDay())
                            ->required(),
                        Forms\Components\CheckboxList::make('dias_semana')
                            ->label('Días de clase')
                            ->options([
                                1 => 'Lunes',
                                2 => 'Martes',
                                3 => 'Miércoles',
                                4 => 'Jueves',
                                5 => 'Viernes',
                                6 => 'Sábado',
                            ])
                            ->default([1, 2, 3, 4, 5])
                            ->required(),
                        Forms\Components\Toggle::make('respetar_feriados')
                            ->label('Respetar y saltar feriados (Cochabamba / UMSS)')
                            ->helperText('Si se activa, los feriados se reemplazan y se programa en el siguiente día hábil disponible.')
                            ->default(true),
                    ])
                    ->action(function (Group $record, array $data) {
                        $holidayService = app(HolidayService::class);
                        $created = $holidayService->generateSessionsForGroup(
                            $record,
                            Carbon::parse($data['fecha_inicio']),
                            $data['dias_semana'],
                            (bool) $data['respetar_feriados']
                        );

                        Notification::make()
                            ->title("Se han programado exitosamente {$created->count()} clases para el grupo.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('exportToSheets')
                    ->label('Exportar a Google Sheets')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('success')
                    ->form([
                        Forms\Components\Radio::make('export_type')
                            ->label('Tipo de planilla')
                            ->options(ExportType::class)
                            ->default(ExportType::BOTH)
                            ->required(),
                    ])
                    ->action(function (Group $record, array $data) {
                        if (! GoogleSheetsService::isConfigured()) {
                            Notification::make()
                                ->title('Google Sheets no configurado')
                                ->body('Configure las credenciales en Admin > Configuración > Google Sheets')
                                ->warning()
                                ->send();

                            return;
                        }

                        $exportType = $data['export_type'] instanceof ExportType
                            ? $data['export_type']
                            : ExportType::from($data['export_type']);

                        try {
                            $tabsCreated = app(SheetExportService::class)->exportGroup($record, $exportType);

                            $spreadsheetId = SystemSetting::get('google_sheets_spreadsheet_id');
                            $sheetUrl = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}";

                            Notification::make()
                                ->title('Exportación exitosa')
                                ->body('Se exportaron '.count($tabsCreated).' pestaña(s): '.implode(', ', $tabsCreated).'. <a href="'.$sheetUrl.'" target="_blank" class="underline">Abrir en Google Sheets</a>')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error en la exportación')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('fusionarGrupo')
                    ->label('Fusión')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->visible(fn (Group $record): bool => $record->status === GroupStatus::NO_HABILITADO)
                    ->form([
                        Forms\Components\Select::make('grupo_destino_id')
                            ->label('Grupo destino')
                            ->options(function (Group $record): array {
                                return Group::query()
                                    ->where('course_id', $record->course_id)
                                    ->where('id', '!=', $record->getKey())
                                    ->where('status', '!=', GroupStatus::CERRADO->value)
                                    ->orderBy('nombre')
                                    ->pluck('nombre', 'id')
                                    ->all();
                            })
                            ->required(),
                    ])
                    ->action(function (Group $record, array $data): void {
                        [$toMove, $destination, $movedCount] = DB::transaction(function () use ($record, $data): array {
                            $origin = Group::query()->whereKey($record->getKey())->lockForUpdate()->firstOrFail();
                            $destination = Group::query()->whereKey((int) $data['grupo_destino_id'])->lockForUpdate()->firstOrFail();

                            if ($destination->getKey() === $origin->getKey()
                                || $destination->course_id !== $origin->course_id
                                || $destination->status === GroupStatus::CERRADO
                            ) {
                                throw ValidationException::withMessages([
                                    'grupo_destino_id' => 'Seleccione un grupo destino válido del mismo curso que no esté cerrado.',
                                ]);
                            }

                            $activeStatuses = [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO];

                            $toMove = $origin->preinscriptions()->whereIn('status', $activeStatuses)->get();
                            $movedCount = $toMove->count();

                            $destinationConfirmed = $destination->preinscriptions()
                                ->whereIn('status', $activeStatuses)
                                ->count();

                            if (($destination->cupo_maximo - $destinationConfirmed) < $movedCount) {
                                throw ValidationException::withMessages([
                                    'grupo_destino_id' => "El grupo destino no tiene cupo disponible para {$movedCount} participante(s).",
                                ]);
                            }

                            if ($movedCount > 0) {
                                foreach ($toMove as $preinscription) {
                                    $preinscription->update(['group_id' => $destination->getKey()]);
                                }
                            }

                            $origin->status = GroupStatus::CERRADO;
                            $origin->save();

                            return [$toMove, $destination, $movedCount];
                        });

                        GroupSheetsNeedRefresh::dispatch($record->getKey());
                        GroupSheetsNeedRefresh::dispatch($destination->getKey());

                        foreach ($toMove as $preinscription) {
                            if (filled($preinscription->email)) {
                                Mail::to($preinscription->email)
                                    ->queue(new GroupMergedNotification($record, $destination, $preinscription));
                            }
                        }

                        Notification::make()
                            ->title("Grupo fusionado: {$movedCount} participante(s) trasladado(s) al grupo destino.")
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

    public static function getRelations(): array
    {
        return [
            GroupResource\RelationManagers\PreinscriptionsRelationManager::class,
        ];
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
