<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case EFECTIVO = 'efectivo';
    case QR = 'qr';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EFECTIVO => 'Efectivo',
            self::QR => 'Código QR',
        };
    }
}
