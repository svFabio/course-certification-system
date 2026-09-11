<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Preinscription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PreinscriptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Preinscription $preinscription): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $preinscription->group->course->instructor_id;
        }

        if ($user->hasRole('student')) {
            return $user->id === $preinscription->user_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    public function update(User $user, Preinscription $preinscription): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $preinscription->group->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, Preinscription $preinscription): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Preinscription $preinscription): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Preinscription $preinscription): bool
    {
        return $user->hasRole('admin');
    }
}
