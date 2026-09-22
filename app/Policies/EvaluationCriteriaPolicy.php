<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EvaluationCriteria;
use App\Models\User;

class EvaluationCriteriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function view(User $user, EvaluationCriteria $evaluationCriteria): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $evaluationCriteria->course->instructor_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function update(User $user, EvaluationCriteria $evaluationCriteria): bool
    {
        if ($user->hasRole(UserRole::ADMIN->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::INSTRUCTOR->value)) {
            return $user->id === $evaluationCriteria->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, EvaluationCriteria $evaluationCriteria): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function restore(User $user, EvaluationCriteria $evaluationCriteria): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }

    public function forceDelete(User $user, EvaluationCriteria $evaluationCriteria): bool
    {
        return $user->hasRole(UserRole::ADMIN->value);
    }
}
