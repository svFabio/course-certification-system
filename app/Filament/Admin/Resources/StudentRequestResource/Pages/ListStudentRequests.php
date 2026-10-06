<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\StudentRequestResource\Pages;

use App\Filament\Admin\Resources\StudentRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentRequests extends ListRecords
{
    protected static string $resource = StudentRequestResource::class;
}
