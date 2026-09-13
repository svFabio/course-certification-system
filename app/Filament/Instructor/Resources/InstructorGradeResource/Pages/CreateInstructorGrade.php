<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorGradeResource\Pages;

use App\Filament\Instructor\Resources\InstructorGradeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstructorGrade extends CreateRecord
{
    protected static string $resource = InstructorGradeResource::class;
}
