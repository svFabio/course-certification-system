<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function view(User $user, Course $course): bool
    {
        return $user->hasRole('admin') || $user->id === $course->instructor_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->hasRole('admin') || $user->id === $course->instructor_id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function restore(User $user, Course $course): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    public function forceDelete(User $user, Course $course): bool
    {
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }
}
