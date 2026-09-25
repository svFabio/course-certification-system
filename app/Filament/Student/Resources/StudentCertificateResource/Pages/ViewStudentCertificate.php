<?php

declare(strict_types=1);

namespace App\Filament\Student\Resources\StudentCertificateResource\Pages;

use App\Filament\Student\Resources\StudentCertificateResource;
use App\Services\CertificateService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentCertificate extends ViewRecord
{
    protected static string $resource = StudentCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('descargar')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn (): string => route('certificado.descargar', $this->record))
                ->openUrlInNewTab(),
            Actions\Action::make('copiarEnlace')
                ->label('Copiar enlace')
                ->icon('heroicon-o-link')
                ->action(function () {
                    $url = app(CertificateService::class)->verificationUrl($this->record);
                    $this->js('window.navigator.clipboard.writeText('.json_encode($url).')');

                    Notification::make()
                        ->title('Enlace de verificación copiado')
                        ->body($url)
                        ->success()
                        ->send();
                }),
        ];
    }
}
