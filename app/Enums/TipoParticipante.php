<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoParticipante: string
{
    case UMSS = 'umss';
    case EXTERNO = 'externo';
    case AUXILIAR = 'auxiliar';
}
