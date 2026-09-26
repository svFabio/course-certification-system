<?php

declare(strict_types=1);

namespace App\Filament\Student\Widgets;

use App\Enums\UserRole;
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
        $certificates = Certificate::whereHas(
            'preinscription',
            fn ($q) => $q->where('email', $email),
        )->count();

        return [
            Stat::make('Mis preinscripciones', (string) $preinscriptions)
                ->description('Cursos en los que te preinscribiste')
                ->color('info'),
            Stat::make('Mis certificados', (string) $certificates)
                ->description('Certificados emitidos a tu nombre')
                ->color('success'),
        ];
    }
}
