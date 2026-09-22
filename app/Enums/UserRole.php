<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case ADMIN = 'admin';
    case INSTRUCTOR = 'instructor';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::INSTRUCTOR => 'Docente / Instructor',
        };
    }
}
