<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CertificateResource\Pages;

use App\Filament\Admin\Resources\CertificateResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCertificate extends ViewRecord
{
    use HasBackButton;

    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\EditAction::make(),
        ];
    }
}
