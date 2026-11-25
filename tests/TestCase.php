<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTestingDatabase();
    }

    /**
     * Abort before any test touches a database whose name does not end in "_testing",
     * so the development database can never be wiped by RefreshDatabase.
     */
    private function ensureTestingDatabase(): void
    {
        $databaseName = DB::connection()->getDatabaseName();

        if (! str_ends_with($databaseName, '_testing')) {
            throw new RuntimeException(
                "Refusing to run tests against database [{$databaseName}]. Tests must use a *_testing database (see phpunit.xml). If config is cached, run: php artisan config:clear"
            );
        }
    }
}
