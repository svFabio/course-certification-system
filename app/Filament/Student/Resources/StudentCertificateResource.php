<?php

declare(strict_types=1);

namespace App\Filament\Student\Resources;

use App\Enums\SignatureStatus;
use App\Enums\UserRole;
use App\Filament\Student\Resources\StudentCertificateResource\Pages;
use App\Models\Certificate;
use App\Services\CertificateService;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentCertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Mi formación';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Mis certificados';

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
            ->whereHas(
                'preinscription',
                fn (Builder $q) => $q->where('email', auth()->user()?->email),
            )
            ->with(['course', 'preinscription']);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Detalle del certificado')
                ->schema([
                    Infolists\Components\TextEntry::make('course.nombre')->label('Curso'),
                    Infolists\Components\TextEntry::make('tipo')->badge()->label('Tipo'),
                    Infolists\Components\TextEntry::make('codigo_unico')->label('Código')->copyable(),
                    Infolists\Components\TextEntry::make('signature_status')
                        ->badge()
                        ->label('Estado')
                        ->formatStateUsing(fn (?SignatureStatus $state): string => $state?->getLabel() ?? '—')
                        ->color(fn (?SignatureStatus $state): string => match ($state) {
                            SignatureStatus::LISTO => 'success',
                            SignatureStatus::PENDIENTE => 'warning',
                            default => 'info',
                        }),
                    Infolists\Components\TextEntry::make('emitido_en')->dateTime('d/m/Y')->label('Emitido'),
                    Infolists\Components\TextEntry::make('verification_url')
                        ->label('Enlace de verificación')
                        ->state(fn (Certificate $record): string => app(CertificateService::class)->verificationUrl($record))
                        ->copyable()
                        ->copyMessage('Enlace copiado')
                        ->visible(fn (Certificate $record): bool => $record->signature_status === SignatureStatus::LISTO)
                        ->columnSpanFull(),
                    Infolists\Components\TextEntry::make('pendiente_ayuda')
                        ->label('Información')
                        ->state('Tu certificado está en proceso de firma por las autoridades. Cuando esté listo podrás descargarlo aquí.')
                        ->visible(fn (Certificate $record): bool => $record->signature_status !== SignatureStatus::LISTO)
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
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->label('Tipo'),
                Tables\Columns\TextColumn::make('codigo_unico')
                    ->label('Código')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('signature_status')
                    ->badge()
                    ->label('Estado')
                    ->formatStateUsing(fn (?SignatureStatus $state): string => $state?->getLabel() ?? '—')
                    ->color(fn (?SignatureStatus $state): string => match ($state) {
                        SignatureStatus::LISTO => 'success',
                        SignatureStatus::PENDIENTE => 'warning',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('emitido_en')
                    ->date('d/m/Y')
                    ->label('Emitido')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('signature_status')
                    ->label('Estado')
                    ->options(SignatureStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('descargar')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Certificate $record): bool => $record->signature_status === SignatureStatus::LISTO)
                    ->url(fn (Certificate $record): string => route('certificado.descargar', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('copiarEnlace')
                    ->label('Copiar enlace')
                    ->icon('heroicon-o-link')
                    ->visible(fn (Certificate $record): bool => $record->signature_status === SignatureStatus::LISTO)
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
                    ->url(fn (Certificate $record): string => static::getUrl('view', ['record' => $record], panel: 'student')),
            ])
            ->emptyStateHeading('Aún no tienes certificados')
            ->emptyStateDescription('Cuando el administrador cierre tu curso, el certificado aparecerá aquí. La descarga se habilita al completar las firmas.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentCertificates::route('/'),
            'view' => Pages\ViewStudentCertificate::route('/{record}'),
        ];
    }
}
