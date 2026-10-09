<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CertificateResource\Pages;

use App\Enums\SignatureStatus;
use App\Filament\Admin\Resources\CertificateResource;
use App\Filament\Concerns\HasBackButton;
use App\Services\CertificateService;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewCertificate extends ViewRecord
{
    use HasBackButton;

    protected static string $resource = CertificateResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Certificado')
                ->schema([
                    Infolists\Components\TextEntry::make('beneficiario')
                        ->label('Beneficiario')
                        ->state(fn ($record): string => $record->beneficiaryName()),
                    Infolists\Components\TextEntry::make('course.nombre')->label('Curso'),
                    Infolists\Components\TextEntry::make('tipo')->badge()->label('Tipo'),
                    Infolists\Components\TextEntry::make('grupos_detalle')
                        ->label('Grupo(s)')
                        ->visible(fn ($record): bool => $record->isInstructorCertificate()),
                    Infolists\Components\TextEntry::make('codigo_unico')->label('Código')->copyable(),
                    Infolists\Components\TextEntry::make('signature_status')->badge()->label('Estado de firma'),
                    Infolists\Components\TextEntry::make('demora')
                        ->label('Demora administrativa')
                        ->state(fn ($record): string => $record->administrativeDelayLabel()),
                    Infolists\Components\TextEntry::make('emitido_en')->dateTime('d/m/Y H:i')->label('Emitido'),
                    Infolists\Components\TextEntry::make('listo_en')->dateTime('d/m/Y H:i')->label('Listo'),
                ])
                ->columns(2),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\Action::make('avanzarFirma')
                ->label(fn (): string => $this->record->signature_status->advanceLabel()
                    ? 'Registrar: '.$this->record->signature_status->advanceLabel()
                    : 'Flujo completo')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->visible(fn (): bool => $this->record->signature_status->next() !== null)
                ->requiresConfirmation()
                ->action(function (CertificateService $service): void {
                    try {
                        $this->record = $service->advanceSignature($this->record);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('No se pudo avanzar la firma')
                            ->body(collect($e->errors())->flatten()->implode(' '))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Firma actualizada')
                        ->body($this->record->signature_status->getLabel())
                        ->success()
                        ->send();
                }),
            Actions\Action::make('reenviarNotificacion')
                ->label('Reenviar notificación')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->visible(fn (): bool => $this->record->signature_status === SignatureStatus::LISTO)
                ->requiresConfirmation()
                ->action(function (CertificateService $service): void {
                    try {
                        $service->resendReadyNotification($this->record);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('No se pudo reenviar')
                            ->body(collect($e->errors())->flatten()->implode(' '))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Notificación reenviada')
                        ->success()
                        ->send();
                }),
            Actions\EditAction::make()->label('Ajustar estado'),
        ];
    }
}
