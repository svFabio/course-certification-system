<?php

use App\Services\CourseService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('courses:close-expired-preinscriptions', function (CourseService $service) {
    $closed = $service->closeExpiredPreinscriptions();
    $this->info("Preinscripciones cerradas: {$closed}");
})->purpose('Cierra la preinscripción de cursos cuya fecha límite ya pasó');

Schedule::command('courses:close-expired-preinscriptions')->daily();
