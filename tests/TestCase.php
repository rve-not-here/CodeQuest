<?php

namespace Tests;

use App\Support\DisposableDatabase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = config('database.default');
        if (DisposableDatabase::allows(
            $connection,
            config("database.connections.{$connection}"),
            getenv('CODEQUEST_ALLOW_DISPOSABLE_DATABASE') === '1',
        )) {
            return $app;
        }

        throw new RuntimeException('Tests require in-memory SQLite or an explicitly approved disposable *_test database with DB_SOCKET cleared.');
    }
}
