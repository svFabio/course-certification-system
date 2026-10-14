<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CertificateType: string implements HasColor, HasLabel
{
    case APROBACION = 'aprobacion';
    case ASISTENCIA = 'asistencia';
    case INSTRUCTOR = 'instructor';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::APROBACION => 'Aprobación',
            self::ASISTENCIA => 'Asistencia',
            self::INSTRUCTOR => 'Instructor',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::APROBACION => 'success',
            self::ASISTENCIA => 'info',
            self::INSTRUCTOR => 'warning',
        };
    }
}
