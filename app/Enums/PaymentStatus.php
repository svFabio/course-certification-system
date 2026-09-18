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

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::VERIFICADO => 'Verificado',
            self::RECHAZADO => 'Rechazado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDIENTE => 'warning',
            self::VERIFICADO => 'success',
            self::RECHAZADO => 'danger',
        };
    }
}
