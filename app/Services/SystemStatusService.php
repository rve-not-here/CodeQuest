<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use PDO;
use Throwable;

/**
 * Operational/infrastructure status for /admin/system (US-711, §33.0). This
 * is infrastructure visibility, not data aggregation like the rest of Phase 7:
 *
 *   - database connectivity is a REAL connection attempt (getPdo + SELECT 1),
 *     never a read of the connection config
 *   - migrations status is a read-only peek at the migrations table
 *   - storage/log health is local filesystem state
 *
 * The secret boundary is structural, the same posture as sensitive model
 * fields: every config value the status may surface must pass through
 * safeConfig(), which refuses any key outside SAFE_CONFIG_KEYS. DB_PASSWORD,
 * APP_KEY, session secrets, API keys, and connection objects can never reach
 * this class's output; the connection-feature read also never echoes exception
 * text (DSN fragments can leak through PDO exception messages).
 */
class SystemStatusService
{
    /**
     * The only config keys this view is allowed to surface. Anything else
     * fails loud in safeConfig(); a future secret read would have to be
     * added here first, where the review is explicit.
     */
    private const SAFE_CONFIG_KEYS = ['app.name', 'app.env', 'app.debug', 'logging.default'];

    /**
     * @return array{
     *     application: array{online: bool, name: string, environment: string, debug: bool, laravel_version: string, php_version: string},
     *     database: array{connected: bool, driver: string, server_version: string|null},
     *     migrations: array{readable: bool, applied: int, latest_batch: int},
     *     storage: array{log_channel: string, logs_writable: bool, log_file_exists: bool, log_file_last_modified: int|null, log_file_size: int|null},
     * }
     */
    public function status(): array
    {
        return [
            'application' => $this->application(),
            'database' => $this->database(),
            'migrations' => $this->migrations(),
            'storage' => $this->storage(),
        ];
    }

    /**
     * @return array{online: bool, name: string, environment: string, debug: bool, laravel_version: string, php_version: string}
     */
    private function application(): array
    {
        return [
            'online' => true,
            'name' => (string) $this->safeConfig('app.name'),
            'environment' => (string) $this->safeConfig('app.env'),
            'debug' => (bool) $this->safeConfig('app.debug'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ];
    }

    /**
     * @return array{connected: bool, driver: string, server_version: string|null}
     */
    private function database(): array
    {
        try {
            $pdo = DB::connection()->getPdo();
            DB::connection()->select('SELECT 1');

            $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

            return [
                'connected' => true,
                'driver' => DB::connection()->getDriverName(),
                'server_version' => is_string($version) ? $version : null,
            ];
        } catch (Throwable) {
            return [
                'connected' => false,
                'driver' => DB::connection()->getDriverName(),
                'server_version' => null,
            ];
        }
    }

    /**
     * @return array{readable: bool, applied: int, latest_batch: int}
     */
    private function migrations(): array
    {
        try {
            return [
                'readable' => true,
                'applied' => (int) DB::table('migrations')->count(),
                'latest_batch' => (int) DB::table('migrations')->max('batch'),
            ];
        } catch (Throwable) {
            return [
                'readable' => false,
                'applied' => 0,
                'latest_batch' => 0,
            ];
        }
    }

    /**
     * @return array{log_channel: string, logs_writable: bool, log_file_exists: bool, log_file_last_modified: int|null, log_file_size: int|null}
     */
    private function storage(): array
    {
        $logsDir = storage_path('logs');
        $logFile = $logsDir.'/laravel.log';

        $fileExists = is_file($logFile);
        $logsWritable = $fileExists ? is_writable($logFile) : is_dir($logsDir) && is_writable($logsDir);

        $lastModified = null;
        $fileSize = null;

        if ($fileExists) {
            try {
                $lastModified = File::lastModified($logFile);
                $fileSize = File::size($logFile);
            } catch (Throwable) {
                // stat failed; surface the file as present but undatable
            }
        }

        return [
            'log_channel' => $this->safeConfig('logging.default'),
            'logs_writable' => $logsWritable,
            'log_file_exists' => $fileExists,
            'log_file_last_modified' => $lastModified,
            'log_file_size' => $fileSize,
        ];
    }

    private function safeConfig(string $key): mixed
    {
        if (! in_array($key, self::SAFE_CONFIG_KEYS, true)) {
            throw new InvalidArgumentException('Refusing to surface config key: '.$key);
        }

        return config($key);
    }
}
