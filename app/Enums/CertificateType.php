<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CertificateType: string implements HasLabel
{
    case APROBACION = 'aprobacion';
    case ASISTENCIA = 'asistencia';

    public function getLabel(): ?string
    {
        return match($this) {
            self::APROBACION => 'Aprobación',
            self::ASISTENCIA => 'Asistencia',
        };
    }
}
