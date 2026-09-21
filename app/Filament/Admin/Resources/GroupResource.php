<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\GroupStatus;
use App\Filament\Admin\Resources\GroupResource\Pages;
use App\Models\Course;
use App\Models\Group;
use App\Models\SystemSetting;
use App\Services\GoogleSheetsService;
use App\Services\HolidayService;
use App\Services\SheetExportBuilder;
use App\Services\TemplateSheetBuilder;
use App\Support\BusinessRules;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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
                    ->default(BusinessRules::MIN_GROUP_CAPACITY)
                    ->required()
                    ->minValue(1),
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
                            ->options([
                                'cash' => 'Planilla de Caja / Inscripción',
                                'teacher' => 'Planilla Docente (Notas y Asistencia)',
                                'both' => 'Ambas planillas',
                            ])
                            ->default('both')
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

                        $builder = new SheetExportBuilder($record);
                        $sheetsService = app(GoogleSheetsService::class);
                        $spreadsheetId = SystemSetting::get('google_sheets_spreadsheet_id');
                        $exportType = $data['export_type'];
                        $tabsCreated = [];

                        try {
                            if ($exportType === 'cash' || $exportType === 'both') {
                                $tabName = $builder->getCashSheetTabName();
                                if (! $sheetsService->checkTabExists($tabName)) {
                                    $sheetsService->createTab($tabName);
                                }
                                $templateBuilder = app(TemplateSheetBuilder::class);
                                $sheetsService->writeCells($tabName.'!A1', $templateBuilder->buildCashTemplate());
                                $sheetsService->writeCells($tabName.'!A7', $builder->buildCashDataRows());
                                $tabsCreated[] = $tabName;
                            }

                            if ($exportType === 'teacher' || $exportType === 'both') {
                                $tabName = $builder->getTeacherSheetTabName();
                                if (! $sheetsService->checkTabExists($tabName)) {
                                    $sheetsService->createTab($tabName);
                                }
                                $templateBuilder = app(TemplateSheetBuilder::class);
                                $sheetsService->writeCells($tabName.'!A1', $templateBuilder->buildTeacherTemplate());
                                $sheetsService->writeCells($tabName.'!A7', $builder->buildTeacherDataRows());
                                $tabsCreated[] = $tabName;
                            }

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
