<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CourseStatus: string implements HasColor, HasLabel
{
    case EN_PREPARACION = 'en_preparacion';
    case PUBLICADO = 'publicado';
    case PREINSCRIPCION_CERRADA = 'preinscripcion_cerrada';
    case EN_CURSO = 'en_curso';
    case FINALIZADO = 'finalizado';
    case CANCELADO = 'cancelado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EN_PREPARACION => 'En preparación',
            self::PUBLICADO => 'Publicado',
            self::PREINSCRIPCION_CERRADA => 'Preinscripción cerrada',
            self::EN_CURSO => 'En curso',
            self::FINALIZADO => 'Finalizado',
            self::CANCELADO => 'Cancelado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EN_PREPARACION => 'warning',
            self::PUBLICADO => 'success',
            self::PREINSCRIPCION_CERRADA => 'gray',
            self::EN_CURSO => 'info',
            self::FINALIZADO => 'success',
            self::CANCELADO => 'danger',
        };
    }
}
