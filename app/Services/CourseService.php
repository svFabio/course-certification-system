<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Course;

class CourseService
{
    public function __construct() {}

    // TODO: Implement publish logic — change status to PUBLICADO
    public function publish(Course $course): Course
    {
        throw new \RuntimeException('TODO: Implement publish');
    }

    // TODO: Implement unpublish logic — change status back to EN_PREPARACION
    public function unpublish(Course $course): Course
    {
        throw new \RuntimeException('TODO: Implement unpublish');
    }

    // TODO: Implement calculatePrice — delegate to BusinessRules
    public function calculatePrice(Course $course, string $participantType): float
    {
        throw new \RuntimeException('TODO: Implement calculatePrice');
    }

    // TODO: Implement getPublishedCourses — return courses with status PUBLICADO
    public function getPublishedCourses()
    {
        throw new \RuntimeException('TODO: Implement getPublishedCourses');
    }
}
