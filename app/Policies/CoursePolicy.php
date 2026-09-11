<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoursePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Course $course): bool
    {
        return $user->hasRole('admin') || $user->id === $course->instructor_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->hasRole('admin') || $user->id === $course->instructor_id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Course $course): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Course $course): bool
    {
        return $user->hasRole('admin');
    }
}
