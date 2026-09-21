<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CourseStatus;
use App\Models\Course;

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
}
