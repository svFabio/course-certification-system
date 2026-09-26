<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SessionResource\Pages;

use App\Filament\Admin\Resources\SessionResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Resources\Pages\CreateRecord;

class CreateSession extends CreateRecord
{
    use HasBackButton;

    protected static string $resource = SessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
        ];
    }
}
