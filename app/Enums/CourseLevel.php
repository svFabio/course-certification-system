<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CourseLevel: string implements HasLabel
{
    case BASICO = 'Básico';
    case INTERMEDIO = 'Intermedio';
    case AVANZADO = 'Avanzado';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
