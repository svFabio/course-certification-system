<?php

declare(strict_types=1);

namespace App\Filament\Student\Resources\StudentCertificateResource\Pages;

use App\Filament\Student\Resources\StudentCertificateResource;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentCertificate extends ViewRecord
{
    protected static string $resource = StudentCertificateResource::class;
}
