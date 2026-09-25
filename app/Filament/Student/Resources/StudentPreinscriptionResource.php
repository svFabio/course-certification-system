<?php

declare(strict_types=1);

namespace App\Filament\Student\Resources;

use App\Enums\UserRole;
use App\Filament\Student\Resources\StudentPreinscriptionResource\Pages;
use App\Models\Preinscription;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StudentPreinscriptionResource extends Resource
{
    protected static ?string $model = Preinscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Mi formación';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Preinscripción';

    protected static ?string $pluralModelLabel = 'Mis preinscripciones';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole(UserRole::STUDENT->value);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('group.course.nombre')->label('Curso'),
            Infolists\Components\TextEntry::make('group.nombre')->label('Grupo'),
            Infolists\Components\TextEntry::make('status')->badge()->label('Estado'),
            Infolists\Components\TextEntry::make('tipo_participante')->label('Tipo de participante'),
            Infolists\Components\TextEntry::make('ci')->label('CI'),
            Infolists\Components\TextEntry::make('email'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->where('email', auth()->user()?->email)
                ->with(['group.course']))
            ->columns([
                Tables\Columns\TextColumn::make('group.course.nombre')
                    ->label('Curso')
                    ->searchable(),
                Tables\Columns\TextColumn::make('group.nombre')
                    ->label('Grupo'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('tipo_participante')
                    ->label('Tipo'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->label('Fecha'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentPreinscriptions::route('/'),
            'view' => Pages\ViewStudentPreinscription::route('/{record}'),
        ];
    }
}
