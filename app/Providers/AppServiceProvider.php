<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Group;
use App\Models\Holiday;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\Session;
use App\Observers\AttendanceObserver;
use App\Observers\CourseObserver;
use App\Observers\GroupObserver;
use App\Observers\HolidayObserver;
use App\Observers\PaymentObserver;
use App\Observers\PreinscriptionObserver;
use App\Observers\SessionObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Attendance::observe(AttendanceObserver::class);
        Course::observe(CourseObserver::class);
        Group::observe(GroupObserver::class);
        Payment::observe(PaymentObserver::class);
        Holiday::observe(HolidayObserver::class);
        Preinscription::observe(PreinscriptionObserver::class);
        Session::observe(SessionObserver::class);
    }
}
