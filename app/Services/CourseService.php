<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Database\Eloquent\Collection;

class CourseService
{
    public function publish(Course $course): Course
    {
        $course->update(['status' => CourseStatus::PUBLICADO]);

        return $course->fresh();
    }

    public function unpublish(Course $course): Course
    {
        $course->update(['status' => CourseStatus::EN_PREPARACION]);

        return $course->fresh();
    }

    public function calculatePrice(Course $course, string $participantType): float
    {
        return match ($participantType) {
            'umss' => (float) $course->precio_umss,
            'externo' => (float) $course->precio_externo,
            'auxiliar' => (float) $course->precio_auxiliar,
            default => throw new \InvalidArgumentException("Invalid participant type: {$participantType}"),
        };
    }

    public function getPublishedCourses(): Collection
    {
        return Course::where('status', CourseStatus::PUBLICADO)
            ->with(['instructor', 'groups'])
            ->get();
    }
}
