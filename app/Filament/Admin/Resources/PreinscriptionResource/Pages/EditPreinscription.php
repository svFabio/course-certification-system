<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PreinscriptionResource\Pages;

use App\Filament\Admin\Resources\PreinscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPreinscription extends EditRecord
{
    protected static string $resource = PreinscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
