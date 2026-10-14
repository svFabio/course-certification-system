<?php

declare(strict_types=1);

namespace App\Filament\Student\Widgets;

use App\Enums\SignatureStatus;
use App\Enums\UserRole;
use App\Filament\Student\Resources\StudentCertificateResource;
use App\Filament\Student\Resources\StudentPreinscriptionResource;
use App\Models\Certificate;
use App\Models\Preinscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole(UserRole::STUDENT->value);
    }

    protected function getStats(): array
    {
        $email = auth()->user()?->email;

        $preinscriptions = Preinscription::where('email', $email)->count();

        $certificatesQuery = Certificate::whereHas(
            'preinscription',
            fn ($q) => $q->where('email', $email),
        );

        $ready = (clone $certificatesQuery)->where('signature_status', SignatureStatus::LISTO)->count();
        $pending = (clone $certificatesQuery)->where('signature_status', '!=', SignatureStatus::LISTO)->count();

        return [
            Stat::make('Mis preinscripciones', (string) $preinscriptions)
                ->description('Cursos en los que te preinscribiste')
                ->color('info')
                ->url(StudentPreinscriptionResource::getUrl('index')),
            Stat::make('Certificados listos', (string) $ready)
                ->description($pending > 0
                    ? "{$pending} en proceso de firma"
                    : 'Disponibles para descargar')
                ->color('success')
                ->url(StudentCertificateResource::getUrl('index')),
        ];
    }
}
