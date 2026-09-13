<?php

declare(strict_types=1);

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::post('/asistencia/registrar', [AttendanceController::class, 'register'])
    ->middleware(['auth', 'web']);
