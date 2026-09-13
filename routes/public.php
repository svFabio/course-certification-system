<?php

use App\Livewire\CatalogComponent;
use App\Livewire\PreinscriptionComponent;
use App\Livewire\CertificateVerificationComponent;
use Illuminate\Support\Facades\Route;

Route::get('/', CatalogComponent::class)->name('home');

Route::get('/preinscripcion/{group}', PreinscriptionComponent::class)->name('preinscripcion');

Route::get('/verificar-certificado', CertificateVerificationComponent::class)->name('certificado.verificar');

Route::get('/asistencia', function () {
    return view('asistencia.escanear');
})->name('asistencia.escanear');
