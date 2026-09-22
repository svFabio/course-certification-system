<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function view(User $user, Group $group): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $group->course->instructor_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function update(User $user, Group $group): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $group->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function restore(User $user, Group $group): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function forceDelete(User $user, Group $group): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }
}
