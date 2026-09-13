<?php
declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorSessionResource\Pages;

use App\Filament\Instructor\Resources\InstructorSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstructorSession extends EditRecord
{
    protected static string $resource = InstructorSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
