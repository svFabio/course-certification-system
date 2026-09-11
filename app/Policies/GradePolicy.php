<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GradePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Grade $grade): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $grade->evaluationCriteria->course->instructor_id;
        }

        if ($user->hasRole('student')) {
            return $user->id === $grade->preinscription->user_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function update(User $user, Grade $grade): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $grade->evaluationCriteria->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Grade $grade): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Grade $grade): bool
    {
        return $user->hasRole('admin');
    }
}
