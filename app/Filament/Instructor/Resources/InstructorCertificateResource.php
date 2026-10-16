<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources;

use App\Enums\UserRole;
use App\Filament\Instructor\Resources\InstructorCertificateResource\Pages;
use App\Models\Certificate;
use App\Services\CertificateService;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InstructorCertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Mi formación';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Mis certificados';

    protected static ?string $modelLabel = 'Certificado';

    protected static ?string $pluralModelLabel = 'Mis certificados';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole(UserRole::INSTRUCTOR->value);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('instructor_id', auth()->id())
            ->whereNull('preinscription_id')
            ->with(['course', 'instructor']);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Detalle del certificado')
                ->schema([
                    Infolists\Components\TextEntry::make('course.nombre')->label('Curso'),
                    Infolists\Components\TextEntry::make('grupos_detalle')->label('Grupo(s)'),
                    Infolists\Components\TextEntry::make('tipo')->badge()->label('Tipo'),
                    Infolists\Components\TextEntry::make('codigo_unico')->label('Código')->copyable(),
                    Infolists\Components\TextEntry::make('emitido_en')->dateTime('d/m/Y')->label('Emitido'),
                    Infolists\Components\TextEntry::make('verification_url')
                        ->label('Enlace de verificación')
                        ->state(fn (Certificate $record): string => app(CertificateService::class)->verificationUrl($record))
                        ->copyable()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('emitido_en', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('course.nombre')
                    ->label('Curso')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('grupos_detalle')
                    ->label('Grupo(s)')
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->label('Tipo'),
                Tables\Columns\TextColumn::make('codigo_unico')
                    ->label('Código')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('emitido_en')
                    ->date('d/m/Y')
                    ->label('Emitido')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('descargar')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Certificate $record): string => route('certificado.descargar', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('copiarEnlace')
                    ->label('Copiar enlace')
                    ->icon('heroicon-o-link')
                    ->action(function (Certificate $record, $livewire): void {
                        $url = app(CertificateService::class)->verificationUrl($record);
                        $livewire->js('window.navigator.clipboard.writeText('.json_encode($url).')');

                        Notification::make()
                            ->title('Enlace de verificación copiado')
                            ->body($url)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->url(fn (Certificate $record): string => static::getUrl('view', ['record' => $record], panel: 'instructor')),
            ])
            ->emptyStateHeading('Aún no tienes certificados')
            ->emptyStateDescription('Cuando el administrador cierre un curso que dictaste, tu certificado aparecerá aquí.')
            ->emptyStateIcon('heroicon-o-document-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructorCertificates::route('/'),
            'view' => Pages\ViewInstructorCertificate::route('/{record}'),
        ];
    }
}
