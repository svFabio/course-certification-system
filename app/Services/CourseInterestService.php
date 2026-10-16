<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\InterestStatus;
use App\Mail\CourseSlotAvailableNotification;
use App\Models\Course;
use App\Models\CourseInterest;
use App\Models\Preinscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CourseInterestService
{
    /**
     * Registers an interest for a course, anonymously or for a logged-in user.
     *
     * Normalizes the email and is idempotent for the active state: if the same
     * email already has an active interest for the course, the existing row is
     * returned instead of throwing. The partial unique index is the last line of
     * defence against concurrent duplicate submissions.
     */
    public function register(Course $course, string $email, ?User $user = null): CourseInterest
    {
        $normalized = $this->normalizeEmail($email);

        if (! filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'Ingrese una dirección de correo electrónico válida.',
            ]);
        }

        $existing = $course->courseInterests()
            ->active()
            ->where('email', $normalized)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        try {
            return $course->courseInterests()->create([
                'email' => $normalized,
                'user_id' => $user?->id,
                'estado' => InterestStatus::INTERESADO,
            ]);
        } catch (UniqueConstraintViolationException) {
            return $course->courseInterests()
                ->active()
                ->where('email', $normalized)
                ->firstOrFail();
        }
    }

    public function activeCountFor(Course $course): int
    {
        return $course->courseInterests()->active()->count();
    }

    /**
     * Marks every not-yet-notified interest of the course as notified and
     * returns how many rows were transitioned. Already notified rows are left
     * untouched so a person is never notified twice.
     */
    public function notifyInterested(Course $course): int
    {
        // Domain invariant: a student who already holds a preinscription for the
        // course must never receive the "slot available" email. Sync conversions
        // first so those interests leave the active selection below.
        $this->syncConversions($course);

        $interests = $course->courseInterests()
            ->where('estado', InterestStatus::INTERESADO)
            ->get();

        if ($interests->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($interests): void {
            foreach ($interests as $interest) {
                $interest->update([
                    'estado' => InterestStatus::NOTIFICADO,
                    'notificado_at' => now(),
                ]);
            }
        });

        foreach ($interests as $interest) {
            Mail::to($interest->email)
                ->queue(new CourseSlotAvailableNotification($course));
        }

        return $interests->count();
    }

    /**
     * Discards active interests for a specific course that has been finalised
     * or cancelled. When $course is omitted the operation is global (admin
     * bulk-clean use-case). Called by CourseObserver on status transition.
     */
    public function archiveForFinishedCourses(?Course $course = null): int
    {
        return DB::transaction(function () use ($course): int {
            $query = CourseInterest::query()->active();

            if ($course !== null) {
                $query->where('course_id', $course->id);
            } else {
                $query->whereHas('course', fn (Builder $query): Builder => $query->whereIn('status', [
                    CourseStatus::FINALIZADO->value,
                    CourseStatus::CANCELADO->value,
                ]));
            }

            return $query->update([
                'estado' => InterestStatus::DESCARTADO->value,
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Links interests to a matching preinscription (same email, same course)
     * and marks them as converted. Matching is by email + course only.
     */
    public function syncConversions(Course $course): int
    {
        $interests = $course->courseInterests()
            ->active()
            ->get();

        $converted = 0;

        foreach ($interests as $interest) {
            $preinscription = Preinscription::query()
                ->where('email', $interest->email)
                ->whereHas('group', fn (Builder $query): Builder => $query->where('course_id', $course->id))
                ->orderByDesc('id')
                ->first();

            if ($preinscription === null) {
                continue;
            }

            $interest->update([
                'preinscription_id' => $preinscription->id,
                'estado' => InterestStatus::CONVERTIDO,
            ]);

            $converted++;
        }

        return $converted;
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
