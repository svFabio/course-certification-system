<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources;

use App\Enums\AttendanceStatus;
use App\Filament\Instructor\Resources\InstructorAttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InstructorAttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Mis Cursos';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Asistencia';

    protected static ?string $modelLabelPlural = 'Asistencias';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('session_id')
                    ->relationship('session', 'fecha')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('preinscription_id')
                    ->relationship('preinscription', 'nombres')
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
                Tables\Columns\TextColumn::make('preinscription.nombres')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->whereHas('session.group.course', fn ($q) => $q->where('instructor_id', auth()->id())))
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructorAttendances::route('/'),
            'view' => Pages\ViewInstructorAttendance::route('/{record}'),
            'edit' => Pages\EditInstructorAttendance::route('/{record}/edit'),
        ];
    }
}
