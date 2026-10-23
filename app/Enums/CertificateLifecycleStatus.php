<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CertificateLifecycleStatus: string implements HasColor, HasLabel
{
    case GENERADO = 'generado';
    case ENTREGADO = 'entregado';
    case DIGITALIZADO = 'digitalizado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::GENERADO => 'Generado',
            self::ENTREGADO => 'Entregado',
            self::DIGITALIZADO => 'Digitalizado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::GENERADO => 'gray',
            self::ENTREGADO => 'warning',
            self::DIGITALIZADO => 'success',
        };
    }

    public function isGenerado(): bool
    {
        return $this === self::GENERADO;
    }

    public function isEntregado(): bool
    {
        return $this === self::ENTREGADO;
    }

    public function isDigitalizado(): bool
    {
        return $this === self::DIGITALIZADO;
    }
}
