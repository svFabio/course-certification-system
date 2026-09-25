<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\InstructorPanelProvider;
use App\Providers\Filament\StudentPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    InstructorPanelProvider::class,
    StudentPanelProvider::class,
];
