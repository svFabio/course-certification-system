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
use App\Support\Storage\FixedCloudinaryStorageAdapter;
use Cloudinary\Cloudinary;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Livewire\Livewire;

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

        $this->app['filesystem']->extend('cloudinary', function ($app, array $config) {
            if (isset($config['url'])) {
                $cloudinary = new Cloudinary($config['url']);
            } else {
                $cloudinary = new Cloudinary([
                    'cloud' => [
                        'cloud_name' => $config['cloud'],
                        'api_key' => $config['key'],
                        'api_secret' => $config['secret'],
                    ],
                    'url' => [
                        'secure' => $config['secure'] ?? false,
                    ],
                ]);
            }

            $adapter = new FixedCloudinaryStorageAdapter($cloudinary, null, $config['prefix'] ?? null);

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

        Livewire::setUpdateRoute(fn ($handle) => Route::post('/livewire/update', $handle)->middleware(['web', 'throttle:60,1']));
    }
}
