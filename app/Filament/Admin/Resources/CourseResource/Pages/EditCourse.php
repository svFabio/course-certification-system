<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    use HasBackButton;

    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
