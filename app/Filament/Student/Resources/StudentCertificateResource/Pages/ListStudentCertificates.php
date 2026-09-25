<?php

declare(strict_types=1);

namespace App\Filament\Student\Resources\StudentCertificateResource\Pages;

use App\Filament\Student\Resources\StudentCertificateResource;
use Filament\Resources\Pages\ListRecords;

class ListStudentCertificates extends ListRecords
{
    protected static string $resource = StudentCertificateResource::class;
}
