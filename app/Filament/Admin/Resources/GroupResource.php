<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\ExportType;
use App\Enums\GroupStatus;
use App\Enums\MergeParticipantDecision;
use App\Enums\PreinscriptionStatus;
use App\Filament\Admin\Resources\GroupResource\Pages;
use App\Models\Course;
use App\Models\Group;
use App\Models\SystemSetting;
use App\Services\GoogleSheetsService;
use App\Services\GroupMergeService;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

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
                        if ($courseId && $state) {
                            $course = Course::find($courseId);
                            if ($course) {
                                $set('hora_fin', BusinessRules::calculateSessionEndTime($state, $course->carga_horaria)->format('H:i'));
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
                    ->minValue(BusinessRules::MIN_GROUP_CAPACITY),
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
                    ->form(function (Group $record): array {
                        $activeParticipants = $record->preinscriptions()
                            ->whereIn('status', [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO])
                            ->orderBy('nombres')
                            ->get();

                        $schema = [
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
                        ];

                        if ($activeParticipants->isNotEmpty()) {
                            $participantFields = [];

                            foreach ($activeParticipants as $participant) {
                                $isInscrito = $participant->status === PreinscriptionStatus::INSCRITO;
                                $statusBadge = $participant->status->getLabel() ?? ($isInscrito ? 'Inscrito' : 'Pendiente de pago');
                                $label = "{$participant->nombres} {$participant->apellido_paterno} (CI: {$participant->ci}) — [{$statusBadge}]";

                                $options = $isInscrito
                                    ? [
                                        MergeParticipantDecision::TRASLADAR->value => MergeParticipantDecision::TRASLADAR->label(),
                                        MergeParticipantDecision::REEMBOLSO->value => MergeParticipantDecision::REEMBOLSO->label(),
                                    ]
                                    : [
                                        MergeParticipantDecision::TRASLADAR->value => MergeParticipantDecision::TRASLADAR->label(),
                                        MergeParticipantDecision::RETIRAR->value => MergeParticipantDecision::RETIRAR->label(),
                                    ];

                                $participantFields[] = Forms\Components\Radio::make("participant_decisions.{$participant->id}")
                                    ->label($label)
                                    ->options($options)
                                    ->default(MergeParticipantDecision::TRASLADAR->value)
                                    ->required();
                            }

                            $schema[] = Forms\Components\Section::make('Decisiones por Participante')
                                ->description('Indique el destino de cada alumno activo del grupo a fusionar.')
                                ->schema($participantFields);
                        }

                        return $schema;
                    })
                    ->action(function (Group $record, array $data): void {
                        $destinationGroup = Group::query()->findOrFail((int) $data['grupo_destino_id']);
                        $decisions = $data['participant_decisions'] ?? [];

                        $result = app(GroupMergeService::class)->assistedMerge($record, $destinationGroup, $decisions);

                        $body = "Trasladados: {$result['movedCount']} | Devoluciones: {$result['refundedCount']} | Retirados: {$result['withdrawnCount']}";

                        Notification::make()
                            ->title('Grupo fusionado con éxito')
                            ->body($body)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(function (Group $record): bool {
                        if (isset($record->preinscriptions_count)) {
                            return (int) $record->preinscriptions_count > 0;
                        }

                        if (isset($record->confirmados) && (int) $record->confirmados > 0) {
                            return true;
                        }

                        return $record->preinscriptions()->exists();
                    })
                    ->tooltip('Solo se pueden eliminar grupos vacíos sin participantes registrados'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records): void {
                            $withStudents = $records->filter(function (Group $g): bool {
                                if (isset($g->preinscriptions_count)) {
                                    return (int) $g->preinscriptions_count > 0;
                                }

                                if (isset($g->confirmados) && (int) $g->confirmados > 0) {
                                    return true;
                                }

                                return $g->preinscriptions()->exists();
                            });

                            if ($withStudents->isNotEmpty()) {
                                Notification::make()
                                    ->title('No se pueden eliminar grupos con estudiantes')
                                    ->body('Uno o más grupos seleccionados tienen participantes matriculados o pagos registrados. Utilice la Fusión Asistida.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $records->each->delete();

                            Notification::make()
                                ->title('Grupos eliminados con éxito')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('preinscriptions');
    }

    public static function getRelations(): array
    {
        return [
            GroupResource\RelationManagers\PreinscriptionsRelationManager::class,
            GroupResource\RelationManagers\SessionsRelationManager::class,
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
