<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorSessionResource\Pages;

use App\Filament\Concerns\HasBackButton;
use App\Filament\Instructor\Resources\InstructorSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditInstructorSession extends EditRecord
{
    use HasBackButton;

    protected static string $resource = InstructorSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        InstructorSessionResource::ensureOwnedGroup($data, $this->getFormStatePath());

        return parent::handleRecordUpdate($record, $data);
    }
}
