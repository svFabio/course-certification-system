<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HolidayResource\Pages;

use App\Filament\Admin\Resources\HolidayResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Resources\Pages\CreateRecord;

class CreateHoliday extends CreateRecord
{
    use HasBackButton;

    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
        ];
    }
}
