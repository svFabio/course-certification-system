<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Group;
use App\Models\Holiday;
use App\Models\Session;
use App\Support\BusinessRules;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HolidayService
{
    /**
     * Check if a given date is a holiday or non-working day.
     */
    public function isHoliday(Carbon|string $date): bool
    {
        $d = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return Holiday::where('activo', true)
            ->whereDate('fecha', $d)
            ->exists();
    }

    /**
     * Check if a given date is a holiday or a weekend (Sunday by default for continuous training).
     */
    public function isNonWorkingDay(Carbon $date, bool $includeSundays = true): bool
    {
        if ($includeSundays && $date->isSunday()) {
            return true;
        }

        return $this->isHoliday($date);
    }

    /**
     * Generate 10 consecutive valid academic sessions starting from a base date.
     * Skips non-working days (holidays and Sundays), but allows manual override.
     *
     * @param  array<int>  $daysOfWeek  ISO-8601 day numbers (1 = Monday, ..., 6 = Saturday)
     * @param  bool  $skipHolidays  If false, classes can take place on holidays
     * @return Collection<int, Session>
     */
    public function generateSessionsForGroup(
        Group $group,
        Carbon $startDate,
        array $daysOfWeek = [1, 2, 3, 4, 5],
        bool $skipHolidays = true
    ): Collection {
        $createdSessions = collect();
        $currentDate = $startDate->copy();
        $count = 0;

        while ($count < BusinessRules::SESSIONS_PER_COURSE) {
            // Check day of week
            if (in_array($currentDate->dayOfWeekIso, $daysOfWeek, true)) {
                // If skipHolidays is true, check if current date is holiday
                if ($skipHolidays && $this->isHoliday($currentDate)) {
                    $currentDate->addDay();

                    continue;
                }

                $session = Session::create([
                    'group_id' => $group->id,
                    'fecha' => $currentDate->toDateString(),
                    'hora_inicio' => $group->hora_inicio,
                    'hora_fin' => $group->hora_fin,
                    'dictada' => false,
                ]);

                $createdSessions->push($session);
                $count++;
            }

            $currentDate->addDay();
        }

        return $createdSessions;
    }

    /**
     * Reschedule / postpone a session to a new date with a documented reason.
     * Leaves full flexibility to reschedule exceptionally (e.g. death, emergency, teacher request).
     */
    public function postponeSession(Session $session, Carbon $newDate, string $reason): Session
    {
        $session->update([
            'fecha_original' => $session->fecha_original ?? $session->fecha,
            'fecha' => $newDate->toDateString(),
            'es_pospuesta' => true,
            'motivo_reprogramacion' => $reason,
        ]);

        return $session->fresh();
    }
}
