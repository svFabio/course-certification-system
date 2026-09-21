<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $attendance->session->group->course->instructor_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function update(User $user, Attendance $attendance): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $attendance->session->group->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function restore(User $user, Attendance $attendance): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function forceDelete(User $user, Attendance $attendance): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }
}
