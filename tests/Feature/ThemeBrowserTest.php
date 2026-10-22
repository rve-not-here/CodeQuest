<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/**
 * The theme choice must survive navigation.
 *
 * A student who picks a theme and clicks straight through to the next page
 * must land on the theme they chose, even when the account write has not
 * landed yet. The page therefore compares the device's choice and the
 * account's by when they were made, and the newer one wins.
 */
class ThemeBrowserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('CODEQUEST_REQUIRE_BROWSER_TESTS') !== '1') {
            $this->markTestSkipped('Browser checks run in the required Chromium integration job.');
        }
    }

    public function test_the_device_choice_wins_when_it_is_newer_than_the_account_choice(): void
    {
        $user = User::factory()->create();
        $preference = UserPreference::factory()->dark()->create(['user_id' => $user->id]);
        DB::table('the404_user_preferences')->where('id', $preference->id)->update(['updated_at' => now()->subHour()]);

        $this->assertTheme(
            $user,
            device: ['theme' => 'light', 'saved_at' => now()->getTimestampMs()],
            expected: 'light',
        );
    }

    public function test_the_account_choice_wins_when_it_is_newer_than_the_device_choice(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->dark()->create(['user_id' => $user->id]);

        $this->assertTheme(
            $user,
            device: ['theme' => 'light', 'saved_at' => now()->subHour()->getTimestampMs()],
            expected: 'dark',
        );
    }

    public function test_the_device_choice_survives_when_the_account_has_no_preference(): void
    {
        $user = User::factory()->create();

        $this->assertTheme(
            $user,
            device: ['theme' => 'dark', 'saved_at' => now()->getTimestampMs()],
            expected: 'dark',
        );
    }

    public function test_a_device_choice_without_a_timestamp_still_wins_and_reaches_the_account(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->dark()->create(['user_id' => $user->id]);

        // A value written before the account learned about time: the student's
        // own choice, not a stale account value.
        $this->assertTheme(
            $user,
            device: ['theme' => 'light', 'saved_at' => null],
            expected: 'light',
            sync: true,
        );
    }

    /**
     * @param  array{theme: string, saved_at: int|null}  $device
     */
    private function assertTheme(User $user, array $device, string $expected, bool $sync = false): void
    {
        Vite::useHotFile(storage_path('framework/testing-browser-no-hot'));

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $result = Process::timeout(30)->input(json_encode([
            'html' => $html,
            'mode' => 'theme',
            'device' => $device,
            'expected' => $expected,
            'sync' => $sync,
        ], JSON_THROW_ON_ERROR))->run(['node', base_path('tests/workspace-browser.mjs')]);

        $this->assertTrue($result->successful(), $result->errorOutput());
        $this->assertStringContainsString('checks passed', $result->output());
    }
}
