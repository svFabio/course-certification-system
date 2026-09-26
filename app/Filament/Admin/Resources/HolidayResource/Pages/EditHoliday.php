<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HolidayResource\Pages;

use App\Filament\Admin\Resources\HolidayResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHoliday extends EditRecord
{
    use HasBackButton;

    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\DeleteAction::make(),
        ];
    }
}
