<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoParticipante: string
{
    case UMSS = 'umss';
    case EXTERNO = 'externo';
    case AUXILIAR = 'auxiliar';

    public function getLabel(): string
    {
        return match ($this) {
            self::UMSS => 'UMSS',
            self::EXTERNO => 'Externo',
            self::AUXILIAR => 'Auxiliar',
        };
    }
}
