<?php
declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorSessionResource\Pages;

use App\Filament\Instructor\Resources\InstructorSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstructorSessions extends ListRecords
{
    protected static string $resource = InstructorSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
