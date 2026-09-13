<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

Route::post('/asistencia/registrar', [AttendanceController::class, 'register'])
    ->middleware(['auth', 'web']);
