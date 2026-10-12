<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Models\Certificate;
use App\Models\Course;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseService
{
    public function __construct(
        private readonly CertificateService $certificateService,
    ) {}

    public function publish(Course $course): Course
    {
        $course->update(['status' => CourseStatus::PUBLICADO]);

        return $course->fresh();
    }

    public function unpublish(Course $course): Course
    {
        $course->update(['status' => CourseStatus::EN_PREPARACION]);

        return $course->fresh();
    }

    /**
     * Close the course and generate digital certificates for enrolled students (HU-28).
     *
     * @return array{course: Course, certificates: Collection<int, Certificate>}
     *
     * @throws ValidationException
     */
    public function close(Course $course): array
    {
        if (in_array($course->status, [CourseStatus::FINALIZADO, CourseStatus::CANCELADO], true)) {
            throw ValidationException::withMessages([
                'course' => 'El curso ya está finalizado o cancelado y no puede cerrarse nuevamente.',
            ]);
        }

        if ($course->status === CourseStatus::EN_PREPARACION) {
            throw ValidationException::withMessages([
                'course' => 'No se puede cerrar un curso que aún está en preparación.',
            ]);
        }

        return DB::transaction(function () use ($course): array {
            $course = Course::whereKey($course->getKey())->lockForUpdate()->firstOrFail();

            if (in_array($course->status, [CourseStatus::FINALIZADO, CourseStatus::CANCELADO], true)) {
                throw ValidationException::withMessages([
                    'course' => 'El curso ya está finalizado o cancelado y no puede cerrarse nuevamente.',
                ]);
            }

            if ($course->status === CourseStatus::EN_PREPARACION) {
                throw ValidationException::withMessages([
                    'course' => 'No se puede cerrar un curso que aún está en preparación.',
                ]);
            }

            $course->update(['status' => CourseStatus::FINALIZADO]);

            $course->groups()->update(['status' => GroupStatus::FINALIZADO]);

            $certificates = $this->certificateService->generateForCourse($course->fresh(['groups.preinscriptions', 'groups.sessions']));

            return [
                'course' => $course->fresh(),
                'certificates' => $certificates,
            ];
        });
    }

    public function canClose(Course $course): bool
    {
        return ! in_array($course->status, [
            CourseStatus::EN_PREPARACION,
            CourseStatus::FINALIZADO,
            CourseStatus::CANCELADO,
        ], true);
    }
}
