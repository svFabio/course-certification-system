<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $attendance->session->group->course->instructor_id;
        }

        if ($user->hasRole('student')) {
            return $user->email === $attendance->preinscription->email;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $attendance->session->group->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function restore(User $user, Attendance $attendance): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function forceDelete(User $user, Attendance $attendance): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }
}
