<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorGradeResource\Pages;

use App\Filament\Instructor\Resources\InstructorGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstructorGrades extends ListRecords
{
    protected static string $resource = InstructorGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
