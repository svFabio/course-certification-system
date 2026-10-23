<?php

declare(strict_types=1);

use App\Enums\ExportType;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Services\GoogleSheetsService;
use App\Services\SheetExportBuilder;
use App\Services\SheetExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\ConfiguresSheets;

uses(RefreshDatabase::class);
uses(ConfiguresSheets::class);

it('writes both tabs when exporting BOTH', function () {
    $this->configureSheets();

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    $builder = new SheetExportBuilder($group);
    $cashTab = $builder->getCashSheetTabName();
    $teacherTab = $builder->getTeacherSheetTabName();

    $sheets = $this->mock(GoogleSheetsService::class);
    $sheets->shouldReceive('setSpreadsheetId')->once()->with('test-spreadsheet-id');
    $sheets->shouldReceive('checkTabExists')->with($cashTab)->once()->andReturn(false);
    $sheets->shouldReceive('createTab')->with($cashTab)->once();
    $sheets->shouldReceive('writeCells')->with($cashTab.'!A1', Mockery::type('array'))->once();
    $sheets->shouldReceive('checkTabExists')->with($teacherTab)->once()->andReturn(false);
    $sheets->shouldReceive('createTab')->with($teacherTab)->once();
    $sheets->shouldReceive('writeCells')->with($teacherTab.'!A1', Mockery::type('array'))->once();
    $sheets->shouldReceive('cleanupDefaultTabs')->once();

    $tabs = app(SheetExportService::class)->exportGroup($group, ExportType::BOTH);

    expect($tabs)->toBe([$cashTab, $teacherTab]);
});

it('sorts students alphabetically and appends total formulas in cash sheet', function () {
    $this->configureSheets();

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    $paterno1 = 'B_'.fake()->lastName();
    $paterno2 = 'A_'.fake()->lastName();

    Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'apellido_paterno' => $paterno1,
        'apellido_materno' => fake()->lastName(),
        'nombres' => fake()->firstName(),
    ]);

    Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'apellido_paterno' => $paterno2,
        'apellido_materno' => fake()->lastName(),
        'nombres' => fake()->firstName(),
    ]);

    $builder = new SheetExportBuilder($group->fresh());
    $rows = $builder->buildCashSheetRows();

    // Data rows start at index 6 (row 7)
    expect($rows[6][3])->toBe($paterno2);
    expect($rows[7][3])->toBe($paterno1);

    // Summary row is at the end
    $summaryRow = end($rows);
    expect($summaryRow[5])->toBe('TOTAL GENERAL');
    expect($summaryRow[7])->toBe('=SUM(H7:H8)');
    expect($summaryRow[8])->toBe('=SUM(I7:I8)');
});

it('sanitizes forbidden characters from group sheet tab names', function () {
    expect(SheetExportBuilder::sanitizeTabName('Grupo 1: Mañana / Tarde *[Test]?\\'))
        ->toBe('Grupo 1- Mañana - Tarde --Test---');
});
