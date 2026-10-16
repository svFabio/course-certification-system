<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\GroupStatus;
use App\Events\GroupSheetsNeedRefresh;
use App\Models\Group;
use App\Services\CourseInterestService;

class GroupObserver
{
    public function __construct(
        private readonly CourseInterestService $courseInterestService,
    ) {}

    /**
     * The planilla headers embed group name, classroom and schedule; refresh
     * the sheets when those change, and refresh the neighbours after the
     * fusion action moves students between groups.
     *
     * When a group transitions to habilitado, notify interested parties so
     * they know a slot is now available for the course.
     */
    public function updated(Group $group): void
    {
        if ($group->isDirty(['nombre', 'aula', 'hora_inicio', 'hora_fin', 'cupo_maximo'])) {
            GroupSheetsNeedRefresh::dispatch($group->id);
        }

        if ($group->isDirty('status') && $group->status === GroupStatus::HABILITADO) {
            $course = $group->course ?? $group->load('course')->course;

            if ($course !== null) {
                $this->courseInterestService->notifyInterested($course);
            }
        }
    }

    public function created(Group $group): void
    {
        if ($group->status === GroupStatus::HABILITADO) {
            $course = $group->course ?? $group->load('course')->course;

            if ($course !== null) {
                $this->courseInterestService->notifyInterested($course);
            }
        }
    }
}
