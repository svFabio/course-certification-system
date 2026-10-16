<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CourseStatus;
use App\Events\GroupSheetsNeedRefresh;
use App\Models\Course;
use App\Models\Group;
use App\Services\CourseInterestService;

class CourseObserver
{
    public function __construct(
        private readonly CourseInterestService $courseInterestService,
    ) {}

    /**
     * The planilla headers embed course name, period, instructor and
     * horario-dependent prices; keep those re-exports fresh when they change.
     *
     * When a course is finalised, discard any remaining open interests so
     * interested parties are not notified for a course that is no longer
     * accepting preinscriptions.
     */
    public function updated(Course $course): void
    {
        if ($course->isDirty(['nombre', 'periodo', 'instructor_id', 'carga_horaria'])) {
            Group::where('course_id', $course->id)
                ->pluck('id')
                ->each(fn (int $groupId) => GroupSheetsNeedRefresh::dispatch($groupId));
        }

        if ($course->isDirty('status') && in_array($course->status, [CourseStatus::FINALIZADO, CourseStatus::CANCELADO], true)) {
            $this->courseInterestService->archiveForFinishedCourses($course);
        }
    }
}
