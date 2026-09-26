<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Filament\Instructor\Resources\InstructorAttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InstructorAttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole(UserRole::INSTRUCTOR->value);
    }

    protected static ?string $navigationGroup = 'Mis Cursos';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Asistencia';

    protected static ?string $pluralModelLabel = 'Asistencias';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('session_id')
                    ->relationship(
                        'session',
                        'fecha',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->whereHas('group.course', fn (Builder $q) => $q->where('instructor_id', auth()->id()))
                            ->orderByDesc('fecha'),
                    )
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('preinscription_id')
                    ->relationship(
                        'preinscription',
                        'nombres',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->whereHas('group.course', fn (Builder $q) => $q->where('instructor_id', auth()->id())),
                    )
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(AttendanceStatus::class)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session.fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('preinscription.full_name')
                    ->label('Participante')
                    ->searchable(['preinscription.nombres', 'preinscription.apellido_paterno', 'preinscription.apellido_materno']),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime()
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->whereHas('session.group.course', fn ($q) => $q->where('instructor_id', auth()->id())))
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructorAttendances::route('/'),
            'view' => Pages\ViewInstructorAttendance::route('/{record}'),
        ];
    }
}
