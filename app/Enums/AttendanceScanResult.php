<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttendanceScanResult: string implements HasLabel
{
    case PRESENTE = 'presente';
    case PARA_REVISION = 'para_revision';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PRESENTE => 'Dentro de rango',
            self::PARA_REVISION => 'Fuera de rango',
        };
    }
}
