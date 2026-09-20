<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum GroupStatus: string implements HasColor, HasLabel
{
    case HABILITADO = 'habilitado';
    case NO_HABILITADO = 'no_habilitado';
    case COMPLETO = 'completo';
    case EN_CURSO = 'en_curso';
    case FINALIZADO = 'finalizado';
    case CERRADO = 'cerrado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::HABILITADO => 'Habilitado',
            self::NO_HABILITADO => 'No habilitado',
            self::COMPLETO => 'Completo',
            self::EN_CURSO => 'En curso',
            self::FINALIZADO => 'Finalizado',
            self::CERRADO => 'Cerrado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::HABILITADO => 'success',
            self::NO_HABILITADO => 'warning',
            self::COMPLETO => 'info',
            self::EN_CURSO => 'primary',
            self::FINALIZADO => 'gray',
            self::CERRADO => 'danger',
        };
    }
}
