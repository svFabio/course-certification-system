<?php

declare(strict_types=1);

namespace App\Filament\Student\Resources\StudentPreinscriptionResource\Pages;

use App\Filament\Student\Resources\StudentPreinscriptionResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentPreinscriptions extends ListRecords
{
    protected static string $resource = StudentPreinscriptionResource::class;
}
