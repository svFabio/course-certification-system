<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PaymentResource\Pages;

use App\Filament\Admin\Resources\PaymentResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    use HasBackButton;

    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\EditAction::make(),
        ];
    }
}
