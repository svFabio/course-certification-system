<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class RedirectByRole
{
    public function hasAccess(User $user): bool
    {
        return $this->panelPath($user) !== null;
    }

    public function redirect(User $user): RedirectResponse
    {
        return redirect($this->panelPath($user) ?? '/');
    }

    private function panelPath(User $user): ?string
    {
        return match (true) {
            $user->hasRole(UserRole::ADMIN->value) => '/admin',
            $user->hasRole(UserRole::INSTRUCTOR->value) => '/instructor',
            $user->hasRole(UserRole::STUDENT->value) => '/estudiante',
            default => null,
        };
    }
}
