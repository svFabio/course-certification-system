<?php

use App\Livewire\LoginComponent;
use Illuminate\Support\Facades\Route;

Route::get('/login', LoginComponent::class)->name('login');

require __DIR__.'/public.php';
