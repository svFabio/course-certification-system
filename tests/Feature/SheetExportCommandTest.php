<?php

declare(strict_types=1);

use App\Enums\ExportType;
use App\Enums\PreinscriptionStatus;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Services\SheetExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\ConfiguresSheets;

uses(RefreshDatabase::class);
uses(ConfiguresSheets::class);

it('exports active groups only', function () {
    $this->configureSheets();

    $course = Course::factory()->create();
    $groupA = Group::factory()->create(['course_id' => $course->id]);
    $groupB = Group::factory()->create(['course_id' => $course->id]);

    Preinscription::factory()->create([
        'group_id' => $groupA->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $this->mock(SheetExportService::class)
        ->shouldReceive('exportGroup')
        ->once()
        ->with(Mockery::on(fn (Group $group) => $group->id === $groupA->id), ExportType::BOTH)
        ->andReturn(['CAJA-'.$groupA->nombre]);

    $this->artisan('sheets:export-groups')
        ->assertSuccessful()
        ->expectsOutputToContain('Grupo '.$groupA->id)
        ->doesntExpectOutputToContain('Grupo '.$groupB->id);
});

it('exports a single group with --group filter', function () {
    $this->configureSheets();

    $course = Course::factory()->create();
    $groupA = Group::factory()->create(['course_id' => $course->id]);
    $groupB = Group::factory()->create(['course_id' => $course->id]);

    Preinscription::factory()->inscrito()->create(['group_id' => $groupA->id]);
    Preinscription::factory()->inscrito()->create(['group_id' => $groupB->id]);

    $this->mock(SheetExportService::class)
        ->shouldReceive('exportGroup')
        ->once()
        ->with(Mockery::on(fn (Group $group) => $group->id === $groupA->id), ExportType::BOTH)
        ->andReturn(['CAJA-'.$groupA->nombre]);

    $this->artisan('sheets:export-groups', ['--group' => $groupA->id])
        ->assertSuccessful()
        ->expectsOutputToContain('Grupo '.$groupA->id)
        ->doesntExpectOutputToContain('Grupo '.$groupB->id);
});

it('fails when google sheets is not configured', function () {
    $this->artisan('sheets:export-groups')
        ->assertFailed()
        ->expectsOutputToContain('Google Sheets no configurado');
});

it('returns failure when export fails for a group', function () {
    $this->configureSheets();

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $this->mock(SheetExportService::class)
        ->shouldReceive('exportGroup')
        ->once()
        ->andThrow(new RuntimeException('boom'));

    $this->artisan('sheets:export-groups')
        ->assertFailed()
        ->expectsOutputToContain('boom')
        ->expectsOutputToContain('errores');
});
