<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorSessionResource\Pages;

use App\Filament\Concerns\HasBackButton;
use App\Filament\Instructor\Resources\InstructorSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInstructorSession extends ViewRecord
{
    use HasBackButton;

    protected static string $resource = InstructorSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\EditAction::make(),
        ];
    }
}
