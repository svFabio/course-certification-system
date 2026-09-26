<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $payment->preinscription->group->course->instructor_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function update(User $user, Payment $payment): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $payment->preinscription->group->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $payment->preinscription->group->course->instructor_id;
        }

        return false;
    }

    public function restore(User $user, Payment $payment): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $payment->preinscription->group->course->instructor_id;
        }

        return false;
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $payment->preinscription->group->course->instructor_id;
        }

        return false;
    }
}
