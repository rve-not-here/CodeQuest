<?php

namespace App\Support;

final class DisposableDatabase
{
    public static function allows(mixed $connection, mixed $configuration, bool $approvedServerDatabase): bool
    {
        if (! is_string($connection) || ! is_array($configuration)
            || ($configuration['driver'] ?? null) !== $connection
            || ! in_array($configuration['url'] ?? null, [null, ''], true)) {
            return false;
        }

        $database = $configuration['database'] ?? null;
        if ($connection === 'sqlite' && $database === ':memory:') {
            return true;
        }

        return $approvedServerDatabase && in_array($connection, ['mysql', 'mariadb'], true)
            && is_string($database) && str_ends_with($database, '_test')
            && ($configuration['unix_socket'] ?? null) === '';
    }
}
