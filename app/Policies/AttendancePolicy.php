<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
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
            return $user->id === $attendance->preinscription->user_id;
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
        return $user->hasRole('admin');
    }

    public function restore(User $user, Attendance $attendance): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Attendance $attendance): bool
    {
        return $user->hasRole('admin');
    }
}
