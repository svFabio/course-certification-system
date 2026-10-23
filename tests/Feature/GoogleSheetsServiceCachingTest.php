<?php

declare(strict_types=1);

use App\Services\GoogleSheetsService;
use Google\Service\Sheets as SheetsClient;
use Google\Service\Sheets\Resource\Spreadsheets;
use Google\Service\Sheets\Resource\SpreadsheetsValues;
use Google\Service\Sheets\Sheet;
use Google\Service\Sheets\SheetProperties;
use Google\Service\Sheets\Spreadsheet;

/*
 * The Google API client is mocked and injected through reflection, so these
 * tests run without Google credentials, network access, or a database.
 */
beforeEach(function () {
    $this->sheetFetches = 0;
    $this->batchUpdates = 0;
    $this->cellWrites = 0;

    $this->spreadsheet = new Spreadsheet([
        'sheets' => [
            new Sheet(['properties' => new SheetProperties(['title' => 'CAJA', 'sheetId' => 11])]),
            new Sheet(['properties' => new SheetProperties(['title' => 'DOCENTE', 'sheetId' => 12])]),
        ],
    ]);

    $spreadsheetsResource = Mockery::mock(Spreadsheets::class);
    $spreadsheetsResource->shouldReceive('get')->andReturnUsing(function (): Spreadsheet {
        $this->sheetFetches++;

        return $this->spreadsheet;
    });
    $spreadsheetsResource->shouldReceive('batchUpdate')->andReturnUsing(function () {
        $this->batchUpdates++;

        return null;
    });

    $valuesResource = Mockery::mock(SpreadsheetsValues::class);
    $valuesResource->shouldReceive('update')->andReturnUsing(function () {
        $this->cellWrites++;

        return null;
    });

    $sheets = Mockery::mock(SheetsClient::class);
    $sheets->spreadsheets = $spreadsheetsResource;
    $sheets->spreadsheets_values = $valuesResource;

    $service = (new ReflectionClass(GoogleSheetsService::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(GoogleSheetsService::class, 'spreadsheetId'))->setValue($service, 'test-spreadsheet-id');
    (new ReflectionProperty(GoogleSheetsService::class, 'service'))->setValue($service, $sheets);

    $this->service = $service;
});

it('fetches the whole spreadsheet only once for repeated tab lookups on one instance', function () {
    expect($this->service->checkTabExists('CAJA'))->toBeTrue()
        ->and($this->service->getSheetIdByName('DOCENTE'))->toBe(12)
        ->and($this->sheetFetches)->toBe(1);
});

it('refetches the spreadsheet after a tab is created so the new sheet is visible', function () {
    expect($this->service->checkTabExists('NUEVA'))->toBeFalse()
        ->and($this->sheetFetches)->toBe(1);

    $this->service->createTab('NUEVA');

    expect($this->batchUpdates)->toBe(1)
        ->and($this->service->checkTabExists('CAJA'))->toBeTrue()
        ->and($this->sheetFetches)->toBe(2);
});

it('keeps the memoized tab list across cell writes so one export fetches the spreadsheet once', function () {
    $this->service->writeCells('CAJA!A1', [['x']]);

    expect($this->cellWrites)->toBe(1)
        ->and($this->service->getSheetIdByName('CAJA'))->toBe(11)
        ->and($this->sheetFetches)->toBe(1);
});

it('still reports a missing tab with the same error', function () {
    expect(fn () => $this->service->getSheetIdByName('NO_EXISTE'))
        ->toThrow(InvalidArgumentException::class, "Sheet tab 'NO_EXISTE' not found in spreadsheet.");
});

it('cleans up default and template tabs when content sheets exist', function () {
    $this->spreadsheet = new Spreadsheet([
        'sheets' => [
            new Sheet(['properties' => new SheetProperties(['title' => 'Hoja 1', 'sheetId' => 0])]),
            new Sheet(['properties' => new SheetProperties(['title' => 'PLANTILLA_CAJA', 'sheetId' => 1])]),
            new Sheet(['properties' => new SheetProperties(['title' => 'NOTAS-G1', 'sheetId' => 2])]),
        ],
    ]);

    $this->service->cleanupDefaultTabs();

    expect($this->batchUpdates)->toBe(1);
});
