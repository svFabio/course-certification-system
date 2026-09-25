<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

final class RoleHome
{
    public static function url(?User $user): string
    {
        if (! $user) {
            return '/login';
        }

        if ($user->hasRole('admin')) {
            return '/admin';
        }

        if ($user->hasRole('instructor')) {
            return '/instructor';
        }

        if ($user->hasRole('student')) {
            return '/estudiante';
        }

        return '/';
    }
}
