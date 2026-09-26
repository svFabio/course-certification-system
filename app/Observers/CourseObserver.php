<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\GroupSheetsNeedRefresh;
use App\Models\Course;
use App\Models\Group;

class CourseObserver
{
    /**
     * The planilla headers embed course name, period, instructor and
     * horario-dependent prices; keep those re-exports fresh when they change.
     */
    public function updated(Course $course): void
    {
        if ($course->isDirty(['nombre', 'periodo', 'instructor_id', 'carga_horaria'])) {
            Group::where('course_id', $course->id)
                ->pluck('id')
                ->each(fn (int $groupId) => GroupSheetsNeedRefresh::dispatch($groupId));
        }
    }
}
