<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseClockTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_mysql_family_connections_explicitly_use_utc(): void
    {
        $this->assertSame('+00:00', config('database.connections.mysql.timezone'));
        $this->assertSame('+00:00', config('database.connections.mariadb.timezone'));
    }

    public function test_mariadb_session_clock_matches_utc(): void
    {
        if (DB::getDriverName() !== 'mariadb') {
            $this->markTestSkipped('MariaDB session timezone requires the disposable MariaDB connection.');
        }

        $clock = DB::selectOne('SELECT @@session.time_zone AS session_timezone, TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), NOW()) AS offset_seconds');

        $this->assertSame('+00:00', $clock->session_timezone);
        $this->assertSame(0, (int) $clock->offset_seconds);
    }

    public function test_database_generated_audit_time_matches_application_time(): void
    {
        if (DB::getDriverName() !== 'mariadb') {
            $this->markTestSkipped('Database-generated timestamps require the disposable MariaDB connection.');
        }

        $admin = User::factory()->admin()->create();

        $audit = app(AdminAuditService::class)->record($admin, AdminAuditService::ACTION_USER_UPDATE, 'UTC timestamp verification');

        $audit->refresh();
        $this->assertLessThanOrEqual(60, abs($audit->created_at->getTimestamp() - now()->getTimestamp()));
    }
}
