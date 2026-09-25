<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorAttendanceResource\Pages;

use App\Filament\Instructor\Resources\InstructorAttendanceResource;
use Filament\Resources\Pages\ListRecords;

class ListInstructorAttendances extends ListRecords
{
    protected static string $resource = InstructorAttendanceResource::class;
}
