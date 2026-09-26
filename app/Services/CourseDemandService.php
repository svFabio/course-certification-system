<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Course;
use App\Models\Group;
use Illuminate\Support\Collection;

class CourseDemandService
{
    /**
     * Courses whose every enabled (HABILITADO) group is at or over cupo_maximo.
     * The system has no waitlist, so accumulated demand is measured as fullness:
     * a course justifies an additional group when there is no free seat anywhere.
     */
    public function coursesAtCapacity(): Collection
    {
        $enabledGroups = Group::query()
            ->where('status', GroupStatus::HABILITADO)
            ->withCount([
                'preinscriptions as confirmados' => fn ($q) => $q->whereIn('status', [
                    PreinscriptionStatus::PENDIENTE_PAGO,
                    PreinscriptionStatus::INSCRITO,
                ]),
            ])
            ->get();

        $courseIds = $enabledGroups
            ->groupBy(fn (Group $group): int => (int) $group->course_id)
            ->filter(fn (Collection $groups): bool => $groups->every(
                fn (Group $group): bool => $group->confirmados >= $group->cupo_maximo,
            ))
            ->keys()
            ->unique()
            ->values();

        if ($courseIds->isEmpty()) {
            return new Collection;
        }

        return Course::query()
            ->whereIn('id', $courseIds->all())
            ->get(['id', 'nombre']);
    }
}
