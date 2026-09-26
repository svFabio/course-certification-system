<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorGradeResource\Pages;

use App\Events\GroupSheetsNeedRefresh;
use App\Filament\Instructor\Resources\InstructorGradeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstructorGrade extends CreateRecord
{
    protected static string $resource = InstructorGradeResource::class;

    protected function afterCreate(): void
    {
        $groupId = $this->record->preinscription?->group_id;

        if ($groupId !== null) {
            GroupSheetsNeedRefresh::dispatch($groupId);
        }
    }
}
