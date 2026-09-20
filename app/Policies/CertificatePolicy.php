<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Certificate $certificate): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $certificate->course->instructor_id;
        }

        if ($user->hasRole('student')) {
            return $user->email === $certificate->preinscription->email;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Certificate $certificate): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Certificate $certificate): bool
    {
        return $user->hasRole('admin');
    }
}
