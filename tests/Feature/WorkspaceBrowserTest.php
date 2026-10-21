<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionBehaviorTest;
use App\Models\MissionDraft;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
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
        if (str_starts_with($mode, 'challenge')) {
            $mission->update([
                'description' => "Authored lesson context.\n\nExample: <h1>A heading</h1>.\n\n<script>window.lessonLeak = true</script>",
                'target_html' => '<h1>Reference output</h1>',
                'solution_code' => '<h1>Private reference solution</h1>',
                'hints' => json_encode(['Use the authored heading syntax.']),
            ]);
            MissionDraft::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id, 'code' => '<h1>Restored draft</h1>']);
        }
        $resource = $mission;
        if (str_starts_with($mode, 'boss')) {
            Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id]);
            $resource = Assessment::factory()->create(['course_id' => $mission->course_id, 'status' => 'active']);
            if ($mode !== 'boss-ready') {
                AssessmentAttempt::factory()->started()->create([
                    'assessment_id' => $resource->id,
                    'user_id' => $student->id,
                    'code' => '<h1>Try this</h1>',
                    'status' => match ($mode) {
                        'boss-pending' => 'submitted',
                        'boss-passed' => 'passed',
                        default => 'started',
                    },
                    'score' => $mode === 'boss-passed' ? 100 : null,
                    'submitted_at' => $mode === 'boss-pending' ? now() : null,
                ]);
            }
        }
        if ($mode === 'challenge-unavailable') {
            $mission->update(['validate_rule' => null]);
            MissionBehaviorTest::factory()->create(['mission_id' => $mission->id]);
            config()->set('grader.url', 'http://grader.internal');
            Http::fake(fn () => throw new ConnectionException('Grader unavailable'));
            $this->actingAs($student)->from(route($route, $resource))
                ->post(route('mission.submit', $mission), ['code' => '<h1>Try this</h1>'])
                ->assertRedirect(route($route, $resource));
            $this->assertDatabaseCount('the404_progress', 0);
            $this->assertDatabaseCount('the404_xp_transactions', 0);
            $this->assertDatabaseCount('the404_mission_submission_results', 0);
        }
        $response = $this->actingAs($student)->get(route($route, $resource))->assertOk();
        $messages = Blade::render('<x-status-message dismissible>Draft saved</x-status-message><x-status-message type="error" dismissible>Save failed</x-status-message>');
        $html = str_replace('</body>', $messages.'</body>', $response->getContent());
        $result = Process::timeout(30)->input(json_encode(['html' => $html, 'mode' => $mode], JSON_THROW_ON_ERROR))
            ->run(['node', base_path('tests/workspace-browser.mjs')]);
        $this->assertTrue($result->successful(), $result->errorOutput());
        $this->assertStringContainsString('checks passed', $result->output());
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function screens(): array
    {
        return [
            'challenge' => ['challenge', 'mission.challenge'],
            'challenge-unavailable' => ['challenge-unavailable', 'mission.challenge'],
            'experiment' => ['experiment', 'mission.show'],
            'boss' => ['boss', 'assessment.show'],
            'boss-ready' => ['boss-ready', 'assessment.show'],
            'boss-pending' => ['boss-pending', 'assessment.show'],
            'boss-passed' => ['boss-passed', 'assessment.show'],
        ];
    }

    #[DataProvider('readOnlyScreens')]
    public function test_read_only_surfaces_in_chromium(string $mode, string $route): void
    {
        if (getenv('CODEQUEST_REQUIRE_BROWSER_TESTS') !== '1') {
            $this->markTestSkipped('Browser checks run in the required Chromium integration job.');
        }
        $student = User::factory()->create();
        Vite::useHotFile(storage_path('framework/testing-browser-no-hot'));

        // Build a course tree: two sections, three missions, progress on the
        // first so the dashboard resolves a current course and a next mission.
        $course = Course::factory()->create(['status' => 'active', 'order_num' => 1]);
        $sectionA = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1, 'title' => 'Structure and text']);
        $sectionB = Section::factory()->create(['course_id' => $course->id, 'order_num' => 2, 'title' => 'Links and media']);
        $m1 = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $sectionA->id, 'order_num' => 1, 'title' => 'Heading restoration', 'difficulty' => 'easy', 'points' => 50]);
        $m2 = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $sectionA->id, 'order_num' => 2, 'title' => 'Paragraph reflow', 'difficulty' => 'easy', 'points' => 60]);
        $m3 = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $sectionB->id, 'order_num' => 3, 'title' => 'Image fallback', 'difficulty' => 'medium', 'points' => 90]);
        Progress::factory()->create(['user_id' => $student->id, 'mission_id' => $m1->id, 'pts_earned' => 50]);

        $response = $this->actingAs($student)->get(route($route))->assertOk();
        $html = $response->getContent();
        $result = Process::timeout(30)->input(json_encode(['html' => $html, 'mode' => $mode], JSON_THROW_ON_ERROR))
            ->run(['node', base_path('tests/workspace-browser.mjs')]);
        $this->assertTrue($result->successful(), $result->errorOutput());
        $this->assertStringContainsString('checks passed', $result->output());
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function readOnlyScreens(): array
    {
        return [
            'dashboard' => ['dashboard', 'dashboard'],
            'learning-path' => ['learning-path', 'learning-path'],
        ];
    }
}
