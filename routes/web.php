<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateDownloadController;
use App\Http\Controllers\GoogleSheetsCallbackController;
use App\Http\Controllers\GoogleSheetsDisconnectController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/certificados/{certificate}/descargar', CertificateDownloadController::class)
        ->name('certificado.descargar');
});

Route::middleware(['auth', EnsureUserIsAdmin::class])->group(function () {
    Route::get('/admin/google-sheets/callback', GoogleSheetsCallbackController::class)
        ->name('google-sheets.callback');
    Route::get('/admin/google-sheets/disconnect', GoogleSheetsDisconnectController::class)
        ->name('google-sheets.disconnect');
});

require __DIR__.'/public.php';

