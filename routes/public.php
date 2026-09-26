<?php

declare(strict_types=1);

use App\Http\Controllers\BoletaController;
use App\Livewire\CatalogComponent;
use App\Livewire\CertificateVerificationComponent;
use App\Livewire\PreinscriptionComponent;
use Illuminate\Support\Facades\Route;

Route::get('/', CatalogComponent::class)->name('home');

Route::get('/preinscripcion/{group}', PreinscriptionComponent::class)
    ->middleware('throttle:10,1')
    ->name('preinscripcion');

Route::get('/verificar-certificado', CertificateVerificationComponent::class)->name('certificado.verificar');

Route::get('/boleta/{preinscription}', BoletaController::class)
    ->middleware('signed')
    ->name('boleta.descargar');

Route::get('/asistencia', function () {
    return view('asistencia.escanear');
})->name('asistencia.escanear');
