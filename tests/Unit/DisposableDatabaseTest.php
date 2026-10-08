<?php

namespace Tests\Unit;

use App\Support\DisposableDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DisposableDatabaseTest extends TestCase
{
    #[DataProvider('connections')]
    public function test_only_explicit_disposable_connections_without_url_overrides_are_allowed(string $connection, array $configuration, bool $approved, bool $expected): void
    {
        $this->assertSame($expected, DisposableDatabase::allows($connection, $configuration, $approved));
    }

    /** @return array<string, array{string, array<string, mixed>, bool, bool}> */
    public static function connections(): array
    {
        return [
            'SQLite memory' => ['sqlite', ['driver' => 'sqlite', 'database' => ':memory:', 'url' => null], false, true],
            'SQLite persistent' => ['sqlite', ['driver' => 'sqlite', 'database' => '/tmp/test.sqlite', 'url' => null], false, false],
            'SQLite URL override' => ['sqlite', ['driver' => 'sqlite', 'database' => ':memory:', 'url' => 'sqlite:///real.sqlite'], false, false],
            'approved MariaDB' => ['mariadb', ['driver' => 'mariadb', 'database' => 'codequest_test', 'url' => '', 'unix_socket' => ''], true, true],
            'unapproved MariaDB' => ['mariadb', ['driver' => 'mariadb', 'database' => 'codequest_test', 'url' => '', 'unix_socket' => ''], false, false],
            'MariaDB URL override' => ['mariadb', ['driver' => 'mariadb', 'database' => 'codequest_test', 'url' => 'mysql://localhost/system404', 'unix_socket' => ''], true, false],
            'MySQL URL query override' => ['mysql', ['driver' => 'mysql', 'database' => 'codequest_test', 'url' => 'mysql://localhost/safe_test?database=system404', 'unix_socket' => ''], true, false],
            'production name' => ['mysql', ['driver' => 'mysql', 'database' => 'system404', 'url' => '', 'unix_socket' => ''], true, false],
            'retained socket' => ['mysql', ['driver' => 'mysql', 'database' => 'safe_test', 'url' => '', 'unix_socket' => '/run/mysqld/mysqld.sock'], true, false],
            'wrong driver' => ['mysql', ['driver' => 'pgsql', 'database' => 'safe_test', 'url' => '', 'unix_socket' => ''], true, false],
        ];
    }
}
