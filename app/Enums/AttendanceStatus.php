<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENTE = 'presente';
    case AUSENTE = 'ausente';
    case JUSTIFICADO = 'justificado';
}
