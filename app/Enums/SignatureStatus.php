<?php

declare(strict_types=1);

namespace App\Enums;

enum SignatureStatus: string
{
    case PENDIENTE = 'pendiente';
    case FIRMADO_JEFE_DEPTO = 'firmado_jefe_depto';
    case FIRMADO_DIRECTOR_ACADEMICO = 'firmado_director_academico';
    case FIRMADO_DECANO = 'firmado_decano';
    case LISTO = 'listo';
}
