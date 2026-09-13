<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\InstructorPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    InstructorPanelProvider::class,
];
