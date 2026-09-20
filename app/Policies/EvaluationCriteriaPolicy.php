<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EvaluationCriteria;
use App\Models\User;

class EvaluationCriteriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, EvaluationCriteria $evaluationCriteria): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $evaluationCriteria->course->instructor_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, EvaluationCriteria $evaluationCriteria): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $user->id === $evaluationCriteria->course->instructor_id;
        }

        return false;
    }

    public function delete(User $user, EvaluationCriteria $evaluationCriteria): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, EvaluationCriteria $evaluationCriteria): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, EvaluationCriteria $evaluationCriteria): bool
    {
        return $user->hasRole('admin');
    }
}
