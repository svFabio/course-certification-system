<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Course;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CourseService
{
    public function hasPublishableGroup(Course $course): bool
    {
        return $course->groups()
            ->whereNotNull('hora_inicio')
            ->whereNotNull('hora_fin')
            ->where('cupo_maximo', '>', 0)
            ->exists();
    }

    public function publish(Course $course, CarbonInterface|string $start, CarbonInterface|string $end): Course
    {
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->startOfDay();

        if ($course->status !== CourseStatus::EN_PREPARACION) {
            throw ValidationException::withMessages([
                'status' => 'Solo se puede publicar un curso en preparación.',
            ]);
        }

        if (! $this->hasPublishableGroup($course)) {
            throw ValidationException::withMessages([
                'groups' => 'Un curso solo puede publicarse si tiene al menos un grupo configurado con horario y cupo definidos.',
            ]);
        }

        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'fecha_fin_preinscripcion' => 'La fecha de fin debe ser posterior o igual a la de inicio.',
            ]);
        }

        if ($endDate->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'fecha_fin_preinscripcion' => 'Si la fecha de preinscripción configurada ya pasó, el sistema no permite publicar el curso.',
            ]);
        }

        $course->update([
            'status' => CourseStatus::PUBLICADO,
            'fecha_inicio_preinscripcion' => $startDate->toDateString(),
            'fecha_fin_preinscripcion' => $endDate->toDateString(),
        ]);

        return $course->fresh(['groups', 'instructor']);
    }

    public function unpublish(Course $course): Course
    {
        $course->update(['status' => CourseStatus::EN_PREPARACION]);

        return $course->fresh();
    }

    public function closePreinscription(Course $course): Course
    {
        if ($course->status !== CourseStatus::PUBLICADO) {
            throw ValidationException::withMessages([
                'status' => 'Solo se puede cerrar la preinscripción de un curso publicado.',
            ]);
        }

        $course->update(['status' => CourseStatus::PREINSCRIPCION_CERRADA]);

        return $course->fresh(['groups.preinscriptions', 'instructor']);
    }

    public function closeExpiredPreinscriptions(): int
    {
        $courses = Course::query()
            ->where('status', CourseStatus::PUBLICADO)
            ->whereNotNull('fecha_fin_preinscripcion')
            ->whereDate('fecha_fin_preinscripcion', '<', now()->toDateString())
            ->get();

        foreach ($courses as $course) {
            $course->update(['status' => CourseStatus::PREINSCRIPCION_CERRADA]);
        }

        return $courses->count();
    }

    public function isPreinscriptionOpen(Course $course): bool
    {
        if ($course->status !== CourseStatus::PUBLICADO) {
            return false;
        }

        $today = now()->startOfDay();

        if ($course->fecha_inicio_preinscripcion && $today->lt($course->fecha_inicio_preinscripcion->startOfDay())) {
            return false;
        }

        if ($course->fecha_fin_preinscripcion && $today->gt($course->fecha_fin_preinscripcion->startOfDay())) {
            return false;
        }

        return true;
    }

    public function publicationSummary(Course $course): string
    {
        $course->loadMissing(['groups', 'instructor']);

        $lines = [
            'UMSS — Formación Continua',
            'Resumen de curso para difusión',
            str_repeat('-', 36),
            'Curso: '.$course->nombre,
            'Periodo: '.($course->periodo ?: 'No definido'),
            'Nivel: '.($course->nivel ?: 'No definido'),
            'Carga horaria: '.$course->carga_horaria.' horas',
            'Docente: '.($course->instructor?->name ?: 'Por asignar'),
            'Preinscripción: '.$this->formatDateRange($course),
            '',
            'Costos:',
            '- Comunidad UMSS: Bs. '.number_format((float) $course->precio_umss, 2),
            '- Externos: Bs. '.number_format((float) $course->precio_externo, 2),
            '- Auxiliares: Bs. '.number_format((float) $course->precio_auxiliar, 2),
            '',
            'Horarios y cupos:',
        ];

        foreach ($course->groups as $group) {
            $lines[] = sprintf(
                '- %s: %s a %s | Cupos: %d (mín. %d)',
                $group->nombre,
                $group->hora_inicio?->format('H:i') ?? '--:--',
                $group->hora_fin?->format('H:i') ?? '--:--',
                $group->cupo_maximo,
                $group->cupo_minimo,
            );
        }

        $lines[] = '';
        $lines[] = 'Catálogo: '.rtrim((string) config('app.url'), '/').'/';

        return implode("\n", $lines)."\n";
    }

    public function cuposSummary(Course $course): string
    {
        $course->loadMissing(['groups.preinscriptions', 'instructor']);

        $lines = [
            'UMSS — Formación Continua',
            'Resumen de cupos por grupo',
            str_repeat('-', 36),
            'Curso: '.$course->nombre,
            'Estado: '.$course->status->getLabel(),
            'Preinscripción: '.$this->formatDateRange($course),
            '',
        ];

        foreach ($course->groups as $group) {
            $pendientes = $group->preinscriptions
                ->where('status', PreinscriptionStatus::PENDIENTE_PAGO)
                ->count();
            $inscritos = $group->preinscriptions
                ->where('status', PreinscriptionStatus::INSCRITO)
                ->count();
            $total = $pendientes + $inscritos;

            $lines[] = $group->nombre;
            $lines[] = sprintf(
                '- Horario: %s a %s',
                $group->hora_inicio?->format('H:i') ?? '--:--',
                $group->hora_fin?->format('H:i') ?? '--:--',
            );
            $lines[] = sprintf('- Cupo máximo: %d | Mínimo: %d', $group->cupo_maximo, $group->cupo_minimo);
            $lines[] = sprintf('- Inscritos: %d | Pendientes de pago: %d | Total: %d', $inscritos, $pendientes, $total);
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    public function calculatePrice(Course $course, string $participantType): float
    {
        return match ($participantType) {
            'umss' => (float) $course->precio_umss,
            'externo' => (float) $course->precio_externo,
            'auxiliar' => (float) $course->precio_auxiliar,
            default => throw new \InvalidArgumentException("Invalid participant type: {$participantType}"),
        };
    }

    public function getPublishedCourses(): Collection
    {
        $this->closeExpiredPreinscriptions();

        return Course::where('status', CourseStatus::PUBLICADO)
            ->with(['instructor', 'groups'])
            ->get();
    }

    private function formatDateRange(Course $course): string
    {
        if (! $course->fecha_inicio_preinscripcion || ! $course->fecha_fin_preinscripcion) {
            return 'No definido';
        }

        return $course->fecha_inicio_preinscripcion->format('d/m/Y')
            .' — '
            .$course->fecha_fin_preinscripcion->format('d/m/Y');
    }
}
