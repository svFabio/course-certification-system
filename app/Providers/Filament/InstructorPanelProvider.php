<?php
declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Instructor\Resources;
use App\Http\Middleware\EnsureUserIsInstructor;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class InstructorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('instructor')
            ->path('instructor')
            ->login()
            ->brandName('UMSS - Panel Instructor')
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Gray,
            ])
            ->resources([
                Resources\InstructorCourseResource::class,
                Resources\InstructorSessionResource::class,
                Resources\InstructorAttendanceResource::class,
                Resources\InstructorGradeResource::class,
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
                EnsureUserIsInstructor::class,
            ])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop();
    }
}
