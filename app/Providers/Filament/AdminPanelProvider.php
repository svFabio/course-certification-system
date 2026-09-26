<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Admin\Resources;
use App\Filament\Pages\Settings\GoogleSheetsSettings;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\UpcomingGroupsWidget;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Support\DesignTokens;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
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
            ->brandName('UMSS Cursos')
            ->font('Montserrat')
            ->darkMode(false)
            ->maxContentWidth('full')
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.custom-styles'))
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn () => view('filament.topbar-public-link'))
            ->colors([
                'primary' => Color::hex(DesignTokens::NAVY),
                'danger' => Color::hex(DesignTokens::RED),
                'success' => Color::hex(DesignTokens::GREEN),
                'warning' => Color::hex(DesignTokens::AMBER),
                'info' => Color::hex(DesignTokens::SKY),
                'gray' => Color::Gray,
            ])
            ->navigationGroups([
                'Gestión Académica',
                'Inscripciones',
                'Evaluación & Certificados',
                'Reportes',
                'Configuración',
            ])
            ->resources([
                Resources\CourseResource::class,
                Resources\GroupResource::class,
                Resources\SessionResource::class,
                Resources\AttendanceResource::class,
                Resources\PreinscriptionResource::class,
                Resources\PaymentResource::class,
                Resources\CertificateResource::class,
                Resources\HolidayResource::class,
                Resources\UserResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                GoogleSheetsSettings::class,
            ])
            ->widgets([
                StatsOverviewWidget::class,
                UpcomingGroupsWidget::class,
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
