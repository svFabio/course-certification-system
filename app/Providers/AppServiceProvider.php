<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Holiday;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Observers\HolidayObserver;
use App\Observers\PaymentObserver;
use App\Observers\PreinscriptionObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Payment::observe(PaymentObserver::class);
        Holiday::observe(HolidayObserver::class);
        Preinscription::observe(PreinscriptionObserver::class);
    }
}
