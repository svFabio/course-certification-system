<?php
declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Admin\Resources;
use App\Http\Middleware\EnsureUserIsAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('UMSS - Formación Continua')
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Gray,
            ])
            ->navigationGroups([
                'Gestión Académica',
                'Inscripciones',
                'Evaluación',
                'Certificados',
                'Reportes',
            ])
            ->resources([
                Resources\CourseResource::class,
                Resources\GroupResource::class,
                Resources\SessionResource::class,
                Resources\AttendanceResource::class,
                Resources\PreinscriptionResource::class,
                Resources\PaymentResource::class,
                Resources\EvaluationCriteriaResource::class,
                Resources\GradeResource::class,
                Resources\CertificateResource::class,
                Resources\UserResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsAdmin::class,
            ])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop();
    }
}
