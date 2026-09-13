<?php
declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorGradeResource\Pages;

use App\Filament\Instructor\Resources\InstructorGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInstructorGrade extends ViewRecord
{
    protected static string $resource = InstructorGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
