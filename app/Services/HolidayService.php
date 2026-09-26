<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Group;
use App\Models\Holiday;
use App\Models\Session;
use App\Support\BusinessRules;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

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
     * The destination date must not fall on a holiday or a weekend.
     */
    public function postponeSession(Session $session, Carbon $newDate, string $reason): Session
    {
        if ($newDate->isWeekend() || $this->isHoliday($newDate)) {
            throw ValidationException::withMessages([
                'nueva_fecha' => 'La nueva fecha no puede caer en feriado ni en fin de semana.',
            ]);
        }

        $newDateString = $newDate->toDateString();

        $overlapCondition = function ($query) use ($session, $newDateString): void {
            $query->whereDate('fecha', $newDateString)
                ->where('id', '!=', $session->id)
                ->where(function ($q) use ($session): void {
                    $q->where('hora_inicio', '<', $session->hora_fin->format('H:i:s'))
                        ->where('hora_fin', '>', $session->hora_inicio->format('H:i:s'));
                });
        };

        $sameGroupClash = Session::where('group_id', $session->group_id)
            ->where($overlapCondition)
            ->exists();

        if ($sameGroupClash) {
            throw ValidationException::withMessages([
                'nueva_fecha' => 'La nueva fecha coincide con otra clase ya programada para este grupo.',
            ]);
        }

        $instructorClash = Session::whereHas('group.course', function ($q) use ($session): void {
            $q->where('instructor_id', $session->group->course->instructor_id);
        })
            ->where($overlapCondition)
            ->exists();

        if ($instructorClash) {
            throw ValidationException::withMessages([
                'nueva_fecha' => 'Ya tienes otra clase programada en ese horario para la misma fecha.',
            ]);
        }

        $session->update([
            'fecha_original' => $session->fecha_original ?? $session->fecha,
            'fecha' => $newDateString,
            'es_pospuesta' => true,
            'motivo_reprogramacion' => $reason,
        ]);

        return $session->fresh();
    }
}
