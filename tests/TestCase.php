<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection === 'sqlite' && $database === ':memory:') {
            return $app;
        }

        if (in_array($connection, ['mysql', 'mariadb'], true)
            && getenv('CODEQUEST_ALLOW_DISPOSABLE_DATABASE') === '1'
            && is_string($database) && str_ends_with($database, '_test')
            && config("database.connections.{$connection}.unix_socket") === '') {
            return $app;
        }

        throw new RuntimeException('Tests require in-memory SQLite or an explicitly approved disposable *_test database with DB_SOCKET cleared.');
    }
}
