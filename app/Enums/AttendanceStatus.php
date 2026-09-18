<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttendanceStatus: string implements HasLabel
{
    case PRESENTE = 'presente';
    case AUSENTE = 'ausente';
    case JUSTIFICADO = 'justificado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PRESENTE => 'Presente',
            self::AUSENTE => 'Ausente',
            self::JUSTIFICADO => 'Justificado',
        };
    }
}
