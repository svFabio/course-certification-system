<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use HasBackButton;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
        ];
    }
}
