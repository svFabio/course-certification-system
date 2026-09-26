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
            Infolists\Components\TextEntry::make('signature_status')
                ->badge()
                ->label('Estado')
                ->formatStateUsing(fn ($state) => $state === SignatureStatus::LISTO ? 'Certificado listo' : $state?->getLabel()),
            Infolists\Components\TextEntry::make('emitido_en')->dateTime('d/m/Y')->label('Emitido'),
            Infolists\Components\TextEntry::make('verification_url')
                ->label('Enlace de verificación')
                ->state(fn (Certificate $record): string => app(CertificateService::class)->verificationUrl($record))
                ->copyable()
                ->copyMessage('Enlace copiado')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->where('signature_status', SignatureStatus::LISTO)
                ->whereHas(
                    'preinscription',
                    fn ($q) => $q->where('email', auth()->user()?->email),
                )
                ->with(['course', 'preinscription']))
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
                    ->label('Estado')
                    ->formatStateUsing(fn () => 'Certificado listo')
                    ->color('success'),
                Tables\Columns\TextColumn::make('emitido_en')
                    ->date('d/m/Y')
                    ->label('Emitido'),
            ])
            ->actions([
                Tables\Actions\Action::make('descargar')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Certificate $record): string => route('certificado.descargar', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('copiarEnlace')
                    ->label('Copiar enlace')
                    ->icon('heroicon-o-link')
                    ->action(function (Certificate $record, $livewire) {
                        $url = app(CertificateService::class)->verificationUrl($record);
                        $livewire->js('window.navigator.clipboard.writeText('.json_encode($url).')');

                        Notification::make()
                            ->title('Enlace de verificación copiado')
                            ->body($url)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->emptyStateHeading('Sin certificados listos')
            ->emptyStateDescription('Cuando tu certificado esté listo aparecerá aquí para descargarlo.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentCertificates::route('/'),
            'view' => Pages\ViewStudentCertificate::route('/{record}'),
        ];
    }
}
