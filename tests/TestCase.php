<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Force the testing database before RefreshDatabase can touch anything.
        putenv('DB_DATABASE=plataforma_formacion_testing');
        $_ENV['DB_DATABASE'] = 'plataforma_formacion_testing';
        $_SERVER['DB_DATABASE'] = 'plataforma_formacion_testing';

        $dbHost = (string) (getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($_SERVER['DB_HOST'] ?? '127.0.0.1')));
        putenv("DB_HOST={$dbHost}");
        $_ENV['DB_HOST'] = $dbHost;
        $_SERVER['DB_HOST'] = $dbHost;

        parent::setUp();

        Storage::fake('cloudinary');

        $database = (string) config('database.connections.pgsql.database');

        if ($database !== 'plataforma_formacion_testing') {
            $this->fail("Safety guard: tests must use plataforma_formacion_testing, got [{$database}].");
        }
    }
}
