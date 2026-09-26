<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SessionResource\Pages;

use App\Filament\Admin\Resources\SessionResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSession extends ViewRecord
{
    use HasBackButton;

    protected static string $resource = SessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\EditAction::make(),
        ];
    }
}
