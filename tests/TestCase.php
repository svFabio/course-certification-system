<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Force the testing database before RefreshDatabase can touch anything.
        putenv('DB_DATABASE=plataforma_formacion_testing');
        $_ENV['DB_DATABASE'] = 'plataforma_formacion_testing';
        $_SERVER['DB_DATABASE'] = 'plataforma_formacion_testing';

        putenv('DB_HOST=postgres');
        $_ENV['DB_HOST'] = 'postgres';
        $_SERVER['DB_HOST'] = 'postgres';

        parent::setUp();

        $database = (string) config('database.connections.pgsql.database');

        if ($database !== 'plataforma_formacion_testing') {
            $this->fail("Safety guard: tests must use plataforma_formacion_testing, got [{$database}].");
        }
    }
}
