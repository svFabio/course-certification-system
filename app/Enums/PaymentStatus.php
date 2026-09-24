<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case PENDIENTE = 'pendiente';
    case VERIFICADO = 'verificado';
    case RECHAZADO = 'rechazado';
    case DEVOLUCION_PENDIENTE = 'devolucion_pendiente';
    case REEMBOLSADO = 'reembolsado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::VERIFICADO => 'Verificado',
            self::RECHAZADO => 'Rechazado',
            self::DEVOLUCION_PENDIENTE => 'Devolución pendiente',
            self::REEMBOLSADO => 'Reembolsado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDIENTE => 'warning',
            self::VERIFICADO => 'success',
            self::RECHAZADO => 'danger',
            self::DEVOLUCION_PENDIENTE => 'amber',
            self::REEMBOLSADO => 'sky',
        };
    }
}
