<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorSessionResource\Pages;

use App\Filament\Concerns\HasBackButton;
use App\Filament\Instructor\Resources\InstructorSessionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInstructorSession extends CreateRecord
{
    use HasBackButton;

    protected static string $resource = InstructorSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        InstructorSessionResource::ensureOwnedGroup($data, $this->getFormStatePath());

        return parent::handleRecordCreation($data);
    }
}
