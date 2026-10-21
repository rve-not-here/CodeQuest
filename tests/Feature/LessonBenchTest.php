<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The ungraded workbench — "try it yourself" — now lives on the mission panel
 * itself, so the old /experiment URL folds into the lesson instead of rendering
 * a second page.
 */
class LessonBenchTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function languages(): array
    {
        return [
            'html' => ['html', '<h1>Example heading</h1>'],
            'css' => ['css', 'h1 { color: red; }'],
            'js' => ['js', 'console.log("Example output");'],
            'javascript' => ['javascript', 'console.log("Example output");'],
        ];
    }

    #[DataProvider('languages')]
    public function test_lesson_bench_uses_language_preview_and_preserves_sandbox(string $type, string $source): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create(['type' => $type]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'broken_code' => $source]);
        $response = $this->actingAs($student)->get(route('mission.show', $mission))->assertOk();
        $this->assertSame(1, preg_match('/<script>\s*(\(function[\s\S]*?)<\/script>/', $response->getContent(), $matches));
        $result = Process::timeout(5)->input($matches[1])->run(['node', '--input-type=module', '-e', <<<'JS'
import vm from 'node:vm';
import { previewDocument, previewSandbox } from './resources/js/preview.js';
let script = '';
for await (const chunk of process.stdin) script += chunk;
const elements = new Map();
const document = {
    getElementById(id) {
        if (!elements.has(id)) elements.set(id, { sandbox: { value: '' }, addEventListener() {}, setAttribute() {} });
        return elements.get(id);
    },
    querySelector() { return null; },
};
const CM = {
    html() {}, css() {}, javascript() {},
    EditorView: class { constructor(options) { this.state = { doc: { toString: () => options.doc } }; } },
};
vm.runInNewContext(script, { document, window: { CodeQuest: { CodeMirror: CM, previewDocument, previewSandbox } } });
process.stdout.write(JSON.stringify(elements.get('experiment-preview')));
JS]);
        $this->assertTrue($result->successful(), $result->errorOutput());
        $frame = json_decode($result->output(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(in_array($type, ['js', 'javascript'], true) ? 'allow-scripts' : '', $frame['sandbox']['value']);
        $preview = $frame['srcdoc'];
        if ($type === 'html') {
            $this->assertSame($source, $preview);
        } elseif ($type === 'css') {
            $this->assertStringContainsString('<style>'.$source.'</style>', $preview);
        } else {
            $this->assertStringContainsString('script.textContent = ', $preview);
            $this->assertStringContainsString('experiment-console', $preview);
        }
        $this->assertStringContainsString('sandbox="'.(in_array($type, ['js', 'javascript'], true) ? 'allow-scripts' : '').'"', $response->getContent());
        $this->assertStringNotContainsString('allow-same-origin', $response->getContent());
        $this->assertDatabaseCount('the404_progress', 0);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    public function test_lesson_panel_owns_the_bench_and_the_experiment_url_folds_into_it(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['broken_code' => '<h1>Try this</h1>', 'solution_code' => 'PRIVATE SOLUTION']);
        KnowledgeCheck::factory()->required()->create(['mission_id' => $mission->id]);

        $lesson = $this->actingAs($student)->get(route('mission.show', $mission))->assertOk();

        $lesson->assertSee('Try it yourself')
            ->assertSee('Ungraded')
            ->assertSee('data-practice-mode="code"', false)
            ->assertSee('data-practice-mode="output"', false)
            ->assertSee('Start required check')
            ->assertDontSee(route('mission.experiment', $mission))
            ->assertDontSee('PRIVATE SOLUTION')
            ->assertDontSee('SUBMIT CHALLENGE');

        $this->get(route('mission.experiment', $mission))
            ->assertRedirect(route('mission.show', $mission));
        $this->post(route('mission.experiment', $mission), ['code' => 'valid', 'passed' => true, 'xp' => 10000])->assertStatus(405);
        $this->get(route('mission.challenge', $mission))->assertRedirect(route('mission.show', $mission));
        foreach (['the404_progress', 'the404_xp_transactions', 'the404_assessment_attempts', 'the404_knowledge_check_attempts', 'the404_user_achievements', 'the404_mission_drafts'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_experiment_url_preserves_authentication_role_and_course_gates(): void
    {
        $mission = Mission::factory()->create();
        $url = route('mission.experiment', $mission);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'teacher']))->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->get($url)->assertRedirect(route('mission.show', $mission));
        $mission->course->update(['status' => 'locked']);
        $this->get($url)->assertForbidden();
        $mission->course->update(['status' => 'active']);
        $earlier = Course::factory()->create(['order_num' => $mission->course->order_num - 1]);
        Mission::factory()->create(['course_id' => $earlier->id]);
        $this->get($url)->assertForbidden();
    }
}
