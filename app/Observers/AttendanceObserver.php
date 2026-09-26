<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\GroupSheetsNeedRefresh;
use App\Models\Attendance;

class AttendanceObserver
{
    private function refresh(Attendance $attendance): void
    {
        $groupId = $attendance->session?->group_id;

        if ($groupId !== null) {
            GroupSheetsNeedRefresh::dispatch($groupId);
        }
    }

    public function created(Attendance $attendance): void
    {
        $this->refresh($attendance);
    }

    public function updated(Attendance $attendance): void
    {
        $this->refresh($attendance);
    }

    public function deleted(Attendance $attendance): void
    {
        $this->refresh($attendance);
    }
}
