<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'admin',
    'middleware' => ['auth', 'web', 'filament.admin.auth'],
    'name' => 'admin.',
], function () {
    Route::get('/', function () {
        return redirect()->route('filament.admin.pages.dashboard');
    })->name('dashboard');
});
