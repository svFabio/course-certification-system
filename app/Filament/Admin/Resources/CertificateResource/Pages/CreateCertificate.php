<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CertificateResource\Pages;

use App\Filament\Admin\Resources\CertificateResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Resources\Pages\CreateRecord;

class CreateCertificate extends CreateRecord
{
    use HasBackButton;

    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
        ];
    }
}
