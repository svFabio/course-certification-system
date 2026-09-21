<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Preinscription;
use App\Models\User;

class PreinscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function view(User $user, Preinscription $preinscription): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $preinscription->group->course->instructor_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function update(User $user, Preinscription $preinscription): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $preinscription->group->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, Preinscription $preinscription): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function restore(User $user, Preinscription $preinscription): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function forceDelete(User $user, Preinscription $preinscription): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }
}
