<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SignatureStatus: string implements HasColor, HasLabel
{
    case PENDIENTE = 'pendiente';
    case FIRMADO_JEFE_DEPTO = 'firmado_jefe_depto';
    case FIRMADO_DIRECTOR_ACADEMICO = 'firmado_director_academico';
    case FIRMADO_DECANO = 'firmado_decano';
    case LISTO = 'listo';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente de firma',
            self::FIRMADO_JEFE_DEPTO => 'Firmado por jefe de departamento',
            self::FIRMADO_DIRECTOR_ACADEMICO => 'Firmado por director académico',
            self::FIRMADO_DECANO => 'Firmado por decano',
            self::LISTO => 'Certificado listo',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDIENTE => 'warning',
            self::FIRMADO_JEFE_DEPTO,
            self::FIRMADO_DIRECTOR_ACADEMICO,
            self::FIRMADO_DECANO => 'info',
            self::LISTO => 'success',
        };
    }

    /** @return list<self> */
    public static function flow(): array
    {
        return [
            self::PENDIENTE,
            self::FIRMADO_JEFE_DEPTO,
            self::FIRMADO_DIRECTOR_ACADEMICO,
            self::FIRMADO_DECANO,
            self::LISTO,
        ];
    }

    public function next(): ?self
    {
        $flow = self::flow();
        $index = array_search($this, $flow, true);

        if ($index === false || $index >= count($flow) - 1) {
            return null;
        }

        return $flow[$index + 1];
    }

    public function advanceLabel(): ?string
    {
        $next = $this->next();

        return $next?->getLabel();
    }
}
