<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InterestStatus: string implements HasColor, HasLabel
{
    case INTERESADO = 'interesado';
    case NOTIFICADO = 'notificado';
    case CONVERTIDO = 'convertido';
    case DESCARTADO = 'descartado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::INTERESADO => 'Interesado',
            self::NOTIFICADO => 'Notificado',
            self::CONVERTIDO => 'Convertido',
            self::DESCARTADO => 'Descartado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::INTERESADO => 'warning',
            self::NOTIFICADO => 'info',
            self::CONVERTIDO => 'success',
            self::DESCARTADO => 'gray',
        };
    }
}
