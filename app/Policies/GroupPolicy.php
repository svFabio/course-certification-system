<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Group $group): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $group->course->instructor_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Group $group): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $group->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Group $group): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Group $group): bool
    {
        return $user->hasRole('admin');
    }
}
