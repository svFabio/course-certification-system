<?php

declare(strict_types=1);

namespace App\Filament\Student\Resources;

use App\Enums\UserRole;
use App\Filament\Student\Resources\StudentCertificateResource\Pages;
use App\Models\Certificate;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StudentCertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Mi formación';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Certificado';

    protected static ?string $pluralModelLabel = 'Mis certificados';

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
            Infolists\Components\TextEntry::make('course.nombre')->label('Curso'),
            Infolists\Components\TextEntry::make('tipo')->badge(),
            Infolists\Components\TextEntry::make('codigo_unico')->label('Código'),
            Infolists\Components\TextEntry::make('signature_status')->badge()->label('Firma'),
            Infolists\Components\TextEntry::make('emitido_en')->dateTime('d/m/Y')->label('Emitido'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereHas(
                'preinscription',
                fn ($q) => $q->where('email', auth()->user()?->email),
            )->with(['course', 'preinscription']))
            ->columns([
                Tables\Columns\TextColumn::make('course.nombre')
                    ->label('Curso')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('codigo_unico')
                    ->label('Código')
                    ->copyable(),
                Tables\Columns\TextColumn::make('signature_status')
                    ->badge()
                    ->label('Firma'),
                Tables\Columns\TextColumn::make('emitido_en')
                    ->date('d/m/Y')
                    ->label('Emitido'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentCertificates::route('/'),
            'view' => Pages\ViewStudentCertificate::route('/{record}'),
        ];
    }
}
