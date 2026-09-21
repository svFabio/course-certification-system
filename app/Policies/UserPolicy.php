<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }
}
