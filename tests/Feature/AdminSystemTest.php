<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SystemStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionMethod;
use Tests\TestCase;

class AdminSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_page_reports_application_database_migration_and_storage_status(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.system'));

        $response->assertOk()
            ->assertSee('System Status')
            ->assertSee('ONLINE')
            ->assertSee('CONNECTED')
            ->assertSee('READABLE')
            ->assertSee('WRITABLE')
            ->assertSee('No credentials surfaced')
            ->assertSee(mb_strtoupper((string) config('app.env')))
            ->assertSee(app()->version())
            ->assertSee(PHP_VERSION);

        $content = $response->getContent();

        $this->assertMetric($content, 'laravel_version', (string) app()->version());
        $this->assertMetric($content, 'php_version', PHP_VERSION);
        $this->assertMetric($content, 'migrations_applied', (string) DB::table('migrations')->count());
        $this->assertMetric($content, 'migrations_latest_batch', (string) (int) DB::table('migrations')->max('batch'));
    }

    public function test_database_status_is_a_real_connection_attempt_not_a_config_read(): void
    {
        $admin = User::factory()->admin()->create();

        // Point the default connection at a sqlite file that cannot exist, and
        // keep a fine-looking driver name: the reported state must come from a
        // genuine failed round trip, not from anything in the config. The
        // default must be restored before teardown: RefreshDatabase rolls the
        // test database back through the default connection, and a wrong
        // default would leave that rollback on the wrong (unreachable) one.
        $originalDefault = (string) config('database.default');

        try {
            Config::set('database.default', 'unreachable');
            Config::set('database.connections.unreachable', [
                'driver' => 'sqlite',
                'database' => storage_path('app/no-such-'.Str::uuid()->toString().'.sqlite'),
            ]);

            $response = $this->actingAs($admin)->get(route('admin.system'));

            $response->assertOk()
                ->assertSee('System Status')
                ->assertSee('UNREACHABLE')
                ->assertSee('Database connection failed')
                ->assertDontSee('CONNECTED');

            $this->assertStringNotContainsString('no-such-', $response->getContent());
        } finally {
            Config::set('database.default', $originalDefault);
        }
    }

    public function test_system_page_never_surfaces_credentials_or_application_secrets(): void
    {
        $admin = User::factory()->admin()->create();

        $realAppKey = (string) config('app.key');

        // app.key is NOT overridden: the session layer encrypts with it and a
        // fake value would raise "Unsupported cipher" before the page renders.
        // The real key is asserted absent below, and the allowlist test proves
        // app.key is refused at the gate.
        Config::set('database.connections.mysql.password', 'DB_PASSWORD_SENTINEL_114');
        Config::set('mail.mailers.smtp.password', 'MAIL_PASSWORD_SENTINEL_881');
        Config::set('services.github.client_secret', 'API_SECRET_SENTINEL_227');
        Config::set('session.cookie', 'SESSION_COOKIE_SENTINEL_905');

        $response = $this->actingAs($admin)->get(route('admin.system'));

        $response->assertOk()
            ->assertSee('System Status')
            ->assertDontSee('DB_PASSWORD_SENTINEL_114')
            ->assertDontSee('MAIL_PASSWORD_SENTINEL_881')
            ->assertDontSee('API_SECRET_SENTINEL_227')
            ->assertDontSee('SESSION_COOKIE_SENTINEL_905')
            ->assertDontSee($realAppKey);
    }

    public function test_system_status_service_refuses_every_secret_category_named_in_the_spec(): void
    {
        $service = new SystemStatusService;
        $gate = new ReflectionMethod(SystemStatusService::class, 'safeConfig');
        $gate->setAccessible(true);

        // Positive: a genuinely safe read still passes the gate.
        $this->assertSame('testing', $gate->invoke($service, 'app.env'));

        // One representative key per §33.0 secret category. Any of these must
        // be refused, not just the ones the sentinel test happened to plant.
        $secretCategories = [
            'DB_PASSWORD' => 'database.connections.mysql.password',
            'APP_KEY' => 'app.key',
            'session secrets' => 'session.encrypt',
            'API keys' => 'services.github.client_secret',
            'credentials' => 'mail.mailers.smtp.password',
        ];

        foreach ($secretCategories as $category => $key) {
            try {
                $gate->invoke($service, $key);
                $this->fail("safeConfig() must refuse {$key} ({$category})");
            } catch (InvalidArgumentException) {
                $this->assertTrue(true, "refused {$category}");
            }
        }
    }

    public function test_system_page_rejects_user_scoping_probes(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $probe) {
            $this->actingAs($admin)->get(route('admin.system').'?'.$probe.'=1')->assertForbidden();
        }
    }

    private function metricValue(string $content, string $metric): ?string
    {
        if (preg_match('/data-metric="'.preg_quote($metric, '/').'"[^>]*>([^<]+)</', $content, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    private function assertMetric(string $content, string $metric, string $expected): void
    {
        $this->assertSame($expected, $this->metricValue($content, $metric), "metric {$metric}");
    }
}
