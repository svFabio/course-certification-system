<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Course;
use App\Models\Group;
use App\Support\BusinessRules;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Query builder over the courses that currently hold active interests,
     * exposing the preloaded active interest count per course.
     */
    public function coursesWithInterest(): Builder
    {
        return Course::query()
            ->whereHas('courseInterests', fn (Builder $query): Builder => $query->active())
            ->withCount(['courseInterests as active_interests_count' => fn (Builder $query): Builder => $query->active()])
            ->orderByDesc('active_interests_count');
    }

    /**
     * Whether an active interest count reached the minimum group capacity.
     * Used to warn administrators without blocking the notifying action.
     */
    public function interestThresholdReached(int $activeInterests): bool
    {
        return $activeInterests >= BusinessRules::MIN_GROUP_CAPACITY;
    }
}
