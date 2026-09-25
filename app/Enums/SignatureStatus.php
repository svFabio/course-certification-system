<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SignatureStatus: string implements HasLabel
{
    case PENDIENTE = 'pendiente';
    case FIRMADO_JEFE_DEPTO = 'firmado_jefe_depto';
    case FIRMADO_DIRECTOR_ACADEMICO = 'firmado_director_academico';
    case FIRMADO_DECANO = 'firmado_decano';
    case LISTO = 'listo';

    public function getLabel(): ?string
    {
        return match($this) {
            self::PENDIENTE => 'Pendiente',
            self::FIRMADO_JEFE_DEPTO => 'Firmado – Jefe Depto.',
            self::FIRMADO_DIRECTOR_ACADEMICO => 'Firmado – Director Académico',
            self::FIRMADO_DECANO => 'Firmado – Decano',
            self::LISTO => 'Listo',
        };
    }
}
