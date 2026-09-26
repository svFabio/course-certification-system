<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PreinscriptionStatus: string implements HasColor, HasLabel
{
    case PENDIENTE_PAGO = 'pendiente_pago';
    case INSCRITO = 'inscrito';
    case RETIRADO = 'retirado';
    case RECHAZADO = 'rechazado';
    case DEVOLUCION_PENDIENTE = 'devolucion_pendiente';
    case REEMBOLSADO = 'reembolsado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDIENTE_PAGO => 'Pendiente de pago',
            self::INSCRITO => 'Inscrito',
            self::RETIRADO => 'Retirado',
            self::RECHAZADO => 'Rechazado',
            self::DEVOLUCION_PENDIENTE => 'Devolución pendiente',
            self::REEMBOLSADO => 'Reembolsado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDIENTE_PAGO => 'warning',
            self::INSCRITO => 'success',
            self::RETIRADO => 'gray',
            self::RECHAZADO => 'danger',
            self::DEVOLUCION_PENDIENTE => 'amber',
            self::REEMBOLSADO => 'sky',
        };
    }
}
