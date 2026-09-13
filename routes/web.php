<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/login', '/admin/login')->name('login');

require __DIR__.'/public.php';
