<?php

declare(strict_types=1);

namespace App\Enums;

enum MergeParticipantDecision: string
{
    case TRASLADAR = 'trasladar';
    case REEMBOLSO = 'reembolso';
    case RETIRAR = 'retirar';

    public function label(): string
    {
        return match ($this) {
            self::TRASLADAR => 'Trasladar al grupo destino',
            self::REEMBOLSO => 'Solicitar devolución (horario incompatible)',
            self::RETIRAR => 'Marcar como retirado (desiste por horario)',
        };
    }
}
