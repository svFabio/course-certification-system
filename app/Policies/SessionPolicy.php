<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Session;
use App\Models\User;

class SessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function view(User $user, Session $session): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $session->group->course->instructor_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function update(User $user, Session $session): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $session->group->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, Session $session): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function restore(User $user, Session $session): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function forceDelete(User $user, Session $session): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }
}
