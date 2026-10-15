<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorCertificateResource\Pages;

use App\Filament\Concerns\HasBackButton;
use App\Filament\Instructor\Resources\InstructorCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInstructorCertificate extends ViewRecord
{
    use HasBackButton;

    protected static string $resource = InstructorCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\Action::make('descargar')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn (): string => route('certificado.descargar', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
