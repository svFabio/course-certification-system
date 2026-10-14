<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StudentRequestType: string implements HasColor, HasLabel
{
    case CAMBIO_GRUPO = 'cambio_grupo';
    case DEVOLUCION = 'devolucion';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CAMBIO_GRUPO => 'Cambio de grupo',
            self::DEVOLUCION => 'Devolución',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CAMBIO_GRUPO => 'info',
            self::DEVOLUCION => 'danger',
        };
    }
}
