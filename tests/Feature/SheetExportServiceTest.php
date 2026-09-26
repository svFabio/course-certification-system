<?php

declare(strict_types=1);

use App\Enums\ExportType;
use App\Models\Course;
use App\Models\Group;
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

    $tabs = app(SheetExportService::class)->exportGroup($group, ExportType::BOTH);

    expect($tabs)->toBe([$cashTab, $teacherTab]);
});
