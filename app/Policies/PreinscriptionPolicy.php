<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Preinscription;
use App\Models\User;

class PreinscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
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
            return $user->email === $preinscription->email;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
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
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function restore(User $user, Preinscription $preinscription): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function forceDelete(User $user, Preinscription $preinscription): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }
}
