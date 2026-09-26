<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Widgets;

use App\Enums\PreinscriptionStatus;
use App\Models\Group;
use App\Models\Session;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Str;

class InstructorStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $instructorId = auth()->id();

        $groups = Group::query()
            ->whereHas('course', fn ($q) => $q->where('instructor_id', $instructorId))
            ->withCount([
                'preinscriptions as inscribed_count' => fn ($q) => $q->whereIn('status', [
                    PreinscriptionStatus::INSCRITO->value,
                    PreinscriptionStatus::PENDIENTE_PAGO->value,
                ]),
                'sessions as sessions_count',
            ])
            ->get();

        $totalCourses = (int) $groups->pluck('course_id')->unique()->count();
        $totalStudents = (int) $groups->sum('inscribed_count');
        $totalSessions = (int) $groups->sum('sessions_count');
        $groupLabel = $groups->count().' '.Str::plural('grupo', $groups->count());

        $pendingAttendance = Session::query()
            ->whereIn('group_id', $groups->pluck('id'))
            ->where('fecha', '<', today())
            ->where('dictada', false)
            ->count();

        return [
            Stat::make('Cursos activos', (string) $totalCourses)
                ->description("{$groupLabel} a tu cargo")
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info')
                ->url(route('filament.instructor.resources.instructor-courses.index')),

            Stat::make('Estudiantes inscritos', (string) $totalStudents)
                ->description('Preinscripciones activas en tus grupos')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($totalStudents > 0 ? 'success' : 'gray'),

            Stat::make('Sesiones por marcar', (string) $pendingAttendance)
                ->description($pendingAttendance > 0 ? 'Asistencia sin registrar' : 'Al día')
                ->descriptionIcon($pendingAttendance > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($pendingAttendance > 0 ? 'warning' : 'success')
                ->url(route('filament.instructor.pages.mark-attendance')),

            Stat::make('Sesiones programadas', (string) $totalSessions)
                ->description($totalSessions > 0 ? 'En tus grupos' : 'Sin sesiones aún')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->url(route('filament.instructor.resources.instructor-sessions.index')),
        ];
    }
}
