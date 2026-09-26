<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorAttendanceResource\Pages;

use App\Filament\Concerns\HasBackButton;
use App\Filament\Instructor\Resources\InstructorAttendanceResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInstructorAttendance extends ViewRecord
{
    use HasBackButton;

    protected static string $resource = InstructorAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
        ];
    }
}
