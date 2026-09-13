<?php
declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorAttendanceResource\Pages;

use App\Filament\Instructor\Resources\InstructorAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInstructorAttendance extends ViewRecord
{
    protected static string $resource = InstructorAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
