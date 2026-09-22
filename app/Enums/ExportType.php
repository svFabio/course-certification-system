<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExportType: string implements HasLabel
{
    case CASH = 'cash';
    case TEACHER = 'teacher';
    case BOTH = 'both';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CASH => 'Planilla de Caja / Inscripción',
            self::TEACHER => 'Planilla Docente (Notas y Asistencia)',
            self::BOTH => 'Ambas planillas',
        };
    }
}
