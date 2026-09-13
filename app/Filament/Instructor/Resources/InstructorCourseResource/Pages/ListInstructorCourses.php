<?php
declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorCourseResource\Pages;

use App\Filament\Instructor\Resources\InstructorCourseResource;
use Filament\Resources\Pages\ListRecords;

class ListInstructorCourses extends ListRecords
{
    protected static string $resource = InstructorCourseResource::class;
}
