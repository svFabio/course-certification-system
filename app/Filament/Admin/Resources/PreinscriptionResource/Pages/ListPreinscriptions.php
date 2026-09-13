<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PreinscriptionResource\Pages;

use App\Filament\Admin\Resources\PreinscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPreinscriptions extends ListRecords
{
    protected static string $resource = PreinscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
