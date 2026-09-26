<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class InstructorDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Inicio';

    protected static ?string $title = 'Mi Panel';

    protected static ?int $navigationSort = 0;
}
