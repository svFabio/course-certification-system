<?php

declare(strict_types=1);

namespace App\Enums;

enum CertificateType: string
{
    case APROBACION = 'aprobacion';
    case ASISTENCIA = 'asistencia';
}
