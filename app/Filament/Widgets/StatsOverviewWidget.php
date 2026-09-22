<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pendingPaymentsCount = Payment::where('estado', PaymentStatus::PENDIENTE)->count();

        $activeCoursesCount = Course::where('status', CourseStatus::EN_CURSO)->count();
        $enabledGroupsCount = Group::where('status', GroupStatus::HABILITADO)->count();

        $lastActionPayment = Payment::with(['preinscription', 'verifier'])
            ->whereIn('estado', [PaymentStatus::VERIFICADO, PaymentStatus::RECHAZADO])
            ->whereNotNull('verificado_en')
            ->latest('verificado_en')
            ->first();

        $lastActionDescription = 'Sin acciones recientes';
        $lastActionValue = 'Al día';
        $lastActionColor = 'gray';

        if ($lastActionPayment) {
            $studentName = $lastActionPayment->preinscription
                ? $lastActionPayment->preinscription->nombres.' '.$lastActionPayment->preinscription->apellido_paterno
                : 'Estudiante';
            $timeAgo = $lastActionPayment->verificado_en->diffForHumans();
            $statusLabel = $lastActionPayment->estado === PaymentStatus::VERIFICADO ? 'Verificado' : 'Rechazado';

            $lastActionValue = "{$statusLabel}: {$studentName}";
            $lastActionDescription = "Por {$lastActionPayment->verifier?->name} • {$timeAgo}";
            $lastActionColor = $lastActionPayment->estado === PaymentStatus::VERIFICADO ? 'success' : 'danger';
        }

        return [
            Stat::make('Preinscripciones por Aprobar', (string) $pendingPaymentsCount)
                ->description($pendingPaymentsCount > 0 ? 'Pagos pendientes de revisión' : 'Cero pagos pendientes')
                ->descriptionIcon($pendingPaymentsCount > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->color($pendingPaymentsCount > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.payments.index')),

            Stat::make('Cursos & Grupos Activos', "{$activeCoursesCount} / {$enabledGroupsCount}")
                ->description('Cursos en curso / Grupos habilitados')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Última Acción', $lastActionValue)
                ->description($lastActionDescription)
                ->descriptionIcon('heroicon-m-clock')
                ->color($lastActionColor),
        ];
    }
}
