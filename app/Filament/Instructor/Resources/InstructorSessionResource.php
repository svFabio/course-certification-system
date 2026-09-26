<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources;

use App\Enums\UserRole;
use App\Filament\Instructor\Resources\InstructorSessionResource\Pages;
use App\Models\Group;
use App\Models\Session;
use App\Services\HolidayService;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Grouping\Group as TableGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class InstructorSessionResource extends Resource
{
    protected static ?string $model = Session::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole(UserRole::INSTRUCTOR->value);
    }

    protected static ?string $navigationGroup = 'Mis Cursos';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Historial de Sesión';

    protected static ?string $pluralModelLabel = 'Historial de Sesiones';

    protected static ?string $navigationLabel = 'Historial de Sesiones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('group_id')
                    ->label('Grupo')
                    ->options(fn () => Group::whereHas('course', fn ($q) => $q->where('instructor_id', auth()->id()))
                        ->with('course')
                        ->get()
                        ->mapWithKeys(fn ($g) => [$g->id => "{$g->course->nombre} — {$g->nombre}"]))
                    ->searchable()
                    ->required()
                    ->disabledOn('view'),

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
                Tables\Columns\TextColumn::make('group.course.nombre')
                    ->label('Curso')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('group.nombre')
                    ->label('Grupo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Clase')
                    ->state(fn (Session $record): string => self::dayLabel($record))
                    ->description(fn (Session $record): HtmlString => new HtmlString(implode('<br>', [
                        e($record->hora_inicio->format('H:i').' – '.$record->hora_fin->format('H:i')),
                    ])))
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Session $record): string => match (true) {
                        (bool) $record->dictada => 'Dictada',
                        (bool) $record->es_pospuesta => 'Reprogramada',
                        default => 'Programada',
                    })
                    ->color(fn (Session $record): string => match (true) {
                        (bool) $record->dictada => 'success',
                        (bool) $record->es_pospuesta => 'warning',
                        default => 'info',
                    }),
            ])
            ->defaultSort('fecha', 'desc')
            ->modifyQueryUsing(fn ($query) => $query
                ->whereHas('group.course', fn ($q) => $q->where('instructor_id', auth()->id()))
                ->with('group.course'))
            ->groups([
                TableGroup::make('group_id')
                    ->label('Grupo')
                    ->getTitleFromRecordUsing(fn (Session $record): string => "{$record->group->course->nombre} — {$record->group->nombre}"),
            ])
            ->defaultGroup('group_id')
            ->groupingSettingsHidden()
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'programada' => 'Programada',
                        'dictada' => 'Dictada',
                        'reprogramada' => 'Reprogramada',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'dictada' => $query->where('dictada', true),
                        'reprogramada' => $query->where('es_pospuesta', true)->where('dictada', false),
                        'programada' => $query->where('dictada', false)->where('es_pospuesta', false),
                        default => $query,
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('postpone')
                    ->label('Reprogramar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\DatePicker::make('nueva_fecha')
                            ->label('Nueva fecha para la clase')
                            ->default(fn (Session $record) => $record->fecha->addDays(1))
                            ->required()
                            ->afterOrEqual(today()->toDateString())
                            ->rules([
                                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->label('Más'),
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
            'index' => Pages\ListInstructorSessions::route('/'),
            'create' => Pages\CreateInstructorSession::route('/create'),
            'view' => Pages\ViewInstructorSession::route('/{record}'),
            'edit' => Pages\EditInstructorSession::route('/{record}/edit'),
        ];
    }

    /**
     * Server-side IDOR guard for both create and update. The submitted group_id must
     * exist AND belong to a course taught by the authenticated instructor.
     *
     * The Select uses a plain options() closure, so Filament never applies its
     * implicit exists rule here; and even that rule would not be ownership scoped.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public static function ensureOwnedGroup(array $data, ?string $formStatePath): void
    {
        $groupId = $data['group_id'] ?? null;

        if (blank($groupId)) {
            return;
        }

        $owned = Group::query()
            ->whereKey($groupId)
            ->whereHas('course', fn (Builder $query) => $query->where('instructor_id', auth()->id()))
            ->exists();

        if (! $owned) {
            $errorKey = filled($formStatePath) ? "{$formStatePath}.group_id" : 'group_id';

            throw ValidationException::withMessages([
                $errorKey => 'El grupo seleccionado no existe o no pertenece a sus cursos.',
            ]);
        }
    }

    /**
     * Abbreviated Spanish weekday plus day/month, without any locale dependency.
     */
    private static function dayLabel(Session $record): string
    {
        return ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'][(int) $record->fecha->dayOfWeek]
            .' '.$record->fecha->format('d/m');
    }
}
