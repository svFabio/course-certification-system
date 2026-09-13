<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'instructor',
    'middleware' => ['auth', 'web', 'filament.instructor.auth'],
    'name' => 'instructor.',
], function () {
    Route::get('/', function () {
        return redirect()->route('filament.instructor.pages.dashboard');
    })->name('dashboard');
});
