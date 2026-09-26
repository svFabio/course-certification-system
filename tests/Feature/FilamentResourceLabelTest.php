<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\AttendanceResource;
use App\Filament\Admin\Resources\HolidayResource;
use App\Filament\Admin\Resources\PreinscriptionResource;
use App\Filament\Instructor\Resources\InstructorAttendanceResource;
use App\Filament\Instructor\Resources\InstructorCourseResource;
use App\Filament\Pages\Settings\GoogleSheetsSettings;

it('places holidays in the same navigation group as the settings page', function () {
    expect(HolidayResource::getNavigationGroup())
        ->toBe(GoogleSheetsSettings::getNavigationGroup())
        ->toBe('Configuración');
});

it('labels the holidays navigation entry and plural in Spanish', function () {
    expect(HolidayResource::getNavigationLabel())
        ->toBe('Feriados / Asuetos')
        ->and(HolidayResource::getPluralModelLabel())
        ->toBe('Feriados / Asuetos');
});

it('exposes explicit Spanish plural labels where the english pluralizer fails', function () {
    expect(PreinscriptionResource::getPluralModelLabel())->toBe('Preinscripciones')
        ->and(AttendanceResource::getPluralModelLabel())->toBe('Asistencias')
        ->and(InstructorAttendanceResource::getPluralModelLabel())->toBe('Asistencias')
        ->and(InstructorCourseResource::getPluralModelLabel())->toBe('Cursos');
});

it('does not register a grade resource in the admin panel provider', function () {
    expect(file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php')))
        ->not->toContain('GradeResource::class');
});
