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
            self::UMSS => 'Estudiante regular umss',
            self::EXTERNO => 'Participante Externo',
            self::AUXILIAR => 'Auxiliar de Docencia',
        };
    }
}
