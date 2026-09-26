<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CertificateResource\Pages;

use App\Enums\SignatureStatus;
use App\Filament\Admin\Resources\CertificateResource;
use App\Filament\Concerns\HasBackButton;
use App\Models\Certificate;
use App\Services\CertificateService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCertificate extends EditRecord
{
    use HasBackButton;

    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\ViewAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Certificate $record */
        if (isset($data['signature_status'])) {
            $status = $data['signature_status'] instanceof SignatureStatus
                ? $data['signature_status']
                : SignatureStatus::from((string) $data['signature_status']);

            return app(CertificateService::class)->updateSignatureStatus($record, $status);
        }

        return parent::handleRecordUpdate($record, $data);
    }
}
