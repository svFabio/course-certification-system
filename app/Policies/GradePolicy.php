<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Grade;
use App\Models\User;

class GradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function view(User $user, Grade $grade): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $grade->evaluationCriteria->course->instructor_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function update(User $user, Grade $grade): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $grade->evaluationCriteria->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function restore(User $user, Grade $grade): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function forceDelete(User $user, Grade $grade): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }
}
