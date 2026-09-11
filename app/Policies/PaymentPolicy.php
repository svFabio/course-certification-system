<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $payment->preinscription->group->course->instructor_id;
        }

        if ($user->hasRole('student')) {
            return $user->id === $payment->preinscription->user_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    public function update(User $user, Payment $payment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $payment->preinscription->group->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin');
    }
}
