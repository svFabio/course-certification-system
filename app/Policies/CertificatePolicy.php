<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function view(User $user, Certificate $certificate): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $certificate->course->instructor_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function restore(User $user, Certificate $certificate): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function forceDelete(User $user, Certificate $certificate): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }
}
