<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Resources\InstructorCertificateResource\Pages;

use App\Filament\Instructor\Resources\InstructorCertificateResource;
use Filament\Resources\Pages\ListRecords;

class ListInstructorCertificates extends ListRecords
{
    protected static string $resource = InstructorCertificateResource::class;
}
