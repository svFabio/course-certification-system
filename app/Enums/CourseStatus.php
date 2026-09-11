<?php

declare(strict_types=1);

namespace App\Enums;

enum CourseStatus: string
{
    case EN_PREPARACION = 'en_preparacion';
    case PUBLICADO = 'publicado';
    case PREINSCRIPCION_CERRADA = 'preinscripcion_cerrada';
    case EN_CURSO = 'en_curso';
    case FINALIZADO = 'finalizado';
    case CANCELADO = 'cancelado';
}
