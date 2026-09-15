<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources;

use App\Filament\Instructor\Resources\InstructorSessionResource\Pages;
use App\Models\Session;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InstructorSessionResource extends Resource
{
    protected static ?string $model = Session::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Mis Cursos';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Sesión';

    protected static ?string $modelLabelPlural = 'Sesiones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->whereHas('group.course', fn ($q) => $q->where('instructor_id', auth()->id())))
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
            'index' => Pages\ListInstructorSessions::route('/'),
            'create' => Pages\CreateInstructorSession::route('/create'),
            'view' => Pages\ViewInstructorSession::route('/{record}'),
            'edit' => Pages\EditInstructorSession::route('/{record}/edit'),
        ];
    }
}
