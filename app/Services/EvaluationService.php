<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Course;
use App\Models\Preinscription;

class EvaluationService
{
    public function __construct()
    {
    }

    // TODO: Implement setCriteria — create evaluation criteria for a course
    public function setCriteria(Course $course, array $criteria): void
    {
        throw new \RuntimeException('TODO: Implement setCriteria');
    }

    // TODO: Implement recordGrades — record grades for a preinscription
    public function recordGrades(Preinscription $preinscription, array $grades): void
    {
        throw new \RuntimeException('TODO: Implement recordGrades');
    }

    // TODO: Implement calculateFinalGrade — weighted average based on criteria percentages
    public function calculateFinalGrade(Preinscription $preinscription): float
    {
        throw new \RuntimeException('TODO: Implement calculateFinalGrade');
    }

    // TODO: Implement getGradeReport — return full grade report for a preinscription
    public function getGradeReport(Preinscription $preinscription): array
    {
        throw new \RuntimeException('TODO: Implement getGradeReport');
    }
}
