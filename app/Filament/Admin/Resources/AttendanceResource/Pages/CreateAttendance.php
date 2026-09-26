<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AttendanceResource\Pages;

use App\Filament\Admin\Resources\AttendanceResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendance extends CreateRecord
{
    use HasBackButton;

    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
        ];
    }
}
