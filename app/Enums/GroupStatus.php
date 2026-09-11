<?php

declare(strict_types=1);

namespace App\Enums;

enum GroupStatus: string
{
    case HABILITADO = 'habilitado';
    case NO_HABILITADO = 'no_habilitado';
    case CERRADO = 'cerrado';
}
