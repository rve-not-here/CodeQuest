<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Vite;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WorkspaceBrowserTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('screens')]
    public function test_built_workspace_controls_in_chromium(string $mode, string $route): void
    {
        if (getenv('CODEQUEST_REQUIRE_BROWSER_TESTS') !== '1') {
            $this->markTestSkipped('Browser checks run in the required Chromium integration job.');
        }
        $student = User::factory()->create();
        Vite::useHotFile(storage_path('framework/testing-browser-no-hot'));
        $mission = Mission::factory()->create(['broken_code' => '<h1>Try this</h1>']);
        $response = $this->actingAs($student)->get(route($route, $mission))->assertOk();
        $result = Process::timeout(30)->input(json_encode(['html' => $response->getContent(), 'mode' => $mode], JSON_THROW_ON_ERROR))
            ->run(['node', base_path('tests/workspace-browser.mjs')]);
        $this->assertTrue($result->successful(), $result->errorOutput());
        $this->assertStringContainsString('checks passed', $result->output());
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function screens(): array
    {
        return ['challenge' => ['challenge', 'mission.challenge'], 'experiment' => ['experiment', 'mission.experiment']];
    }
}
