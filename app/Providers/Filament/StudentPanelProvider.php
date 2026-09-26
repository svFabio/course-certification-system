<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Student\Resources;
use App\Filament\Student\Widgets\StudentOverviewWidget;
use App\Http\Middleware\EnsureUserIsStudent;
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

class StudentPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('student')
            ->path('estudiante')
            ->brandName('UMSS - Panel Estudiante')
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
                'Mi formación',
            ])
            ->resources([
                Resources\StudentPreinscriptionResource::class,
                Resources\StudentCertificateResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                StudentOverviewWidget::class,
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
                EnsureUserIsStudent::class,
            ])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop();
    }
}
