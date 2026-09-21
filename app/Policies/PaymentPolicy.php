<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $payment->preinscription->group->course->instructor_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
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
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function restore(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }
}
