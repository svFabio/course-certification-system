<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HolidayResource\Pages;

use App\Filament\Admin\Resources\HolidayResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHoliday extends CreateRecord
{
    protected static string $resource = HolidayResource::class;
}
