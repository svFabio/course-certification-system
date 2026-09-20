<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoParticipante: string implements HasLabel
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
