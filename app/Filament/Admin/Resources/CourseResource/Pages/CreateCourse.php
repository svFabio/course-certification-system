<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Filament\Admin\Resources\CourseResource;
use App\Filament\Concerns\HasBackButton;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    use HasBackButton;

    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
        ];
    }
}
