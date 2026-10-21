<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use Database\Seeders\CurriculumContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Tests\TestCase;

class CurriculumPreviewFixtureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authored_dom_tasks_publish_the_elements_their_starter_code_requires(): void
    {
        $this->seed(CurriculumContentSeeder::class);

        $fixtures = [
            'DOM_Node_Activation' => ['id="status"', 'OFFLINE'],
            'Event_Listener_Rewire' => ['id="emergencyBtn"', '<button'],
            'Query_All' => ['class="lamp"', 'Lamp 1', 'Lamp 2', 'Lamp 3'],
            'Build_Nodes' => ['<ul id="bands"></ul>'],
            'Form_Guard' => ['<form id="signup">', 'id="callsign"', 'name="callsign"', '<label'],
            'Event_Bubble' => ['id="bands"', '<li>UHF</li>', '<li>VHF</li>'],
            'Input_Events' => ['id="callsign"', '<label'],
            'Form_Data' => ['<form id="signup">', 'name="callsign"', '<label'],
        ];

        foreach ($fixtures as $title => $elements) {
            $mission = Mission::query()->where('title', $title)->firstOrFail();
            $this->assertMatchesRegularExpression('/\/\* Preview HTML:\n([\s\S]*?)\n\*\//', $mission->broken_code, $title);
            foreach ($elements as $element) {
                $this->assertStringContainsString($element, $mission->broken_code, $title);
            }
            $this->assertArrayNotHasKey('solution_code', $mission->toArray());
            $this->assertArrayNotHasKey('validate_rule', $mission->toArray());
        }

        $this->assertStringContainsString('/* Preview source only */', Mission::query()->where('title', 'Module_Syntax')->firstOrFail()->broken_code);
    }

    public function test_fixture_publication_versions_new_content_once_without_rewriting_completed_progress(): void
    {
        $this->seed(CurriculumContentSeeder::class);
        $mission = Mission::query()->where('title', 'DOM_Node_Activation')->firstOrFail();
        Mission::withoutEvents(function () use ($mission): void {
            $mission->update([
                'broken_code' => "// DOM node is unresponsive\n// Select and update the element below\n\n// <div id=\"status\">OFFLINE</div>",
                'version' => 4,
            ]);
        });
        $progress = Progress::factory()->create([
            'mission_id' => $mission->id,
            'mission_version' => 4,
            'pts_earned' => 100,
        ]);
        $history = $progress->refresh()->getRawOriginal();
        $solution = $mission->solution_code;
        $rules = $mission->validate_rule;

        $this->seed(CurriculumContentSeeder::class);

        $this->assertSame(5, $mission->refresh()->version);
        $this->assertSame($history, $progress->refresh()->getRawOriginal());
        $this->assertSame($solution, $mission->solution_code);
        $this->assertSame($rules, $mission->validate_rule);
        $published = Mission::query()->orderBy('id')->get()->map->getRawOriginal()->all();

        $this->seed(CurriculumContentSeeder::class);

        $this->assertSame($published, Mission::query()->orderBy('id')->get()->map->getRawOriginal()->all());
        $this->assertSame($history, $progress->refresh()->getRawOriginal());
    }

    public function test_admin_can_publish_only_preview_metadata_without_overwriting_local_content_or_history(): void
    {
        $this->seed(CurriculumContentSeeder::class);
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $operator = User::factory()->create(['role' => 'operator']);
        $titles = [
            'DOM_Node_Activation', 'Event_Listener_Rewire', 'Query_All', 'Build_Nodes',
            'Form_Guard', 'Event_Bubble', 'Input_Events', 'Form_Data', 'Module_Syntax',
        ];

        foreach ($titles as $title) {
            $mission = Mission::query()->where('title', $title)->firstOrFail();
            $this->assertSame(1, preg_match('/(\/\* Preview HTML:\n[\s\S]*?\n\*\/|\/\* Preview source only \*\/)/', $mission->broken_code, $matches), $title);
            $localSource = '// Locally authored exercise for '.$title."\nconsole.log(\"LOCAL CONTENT\");";
            Mission::withoutEvents(function () use ($mission, $localSource): void {
                $mission->update([
                    'broken_code' => $localSource,
                    'hints' => 'Locally reviewed hints',
                    'version' => 4,
                ]);
            });
            $progress = Progress::factory()->create([
                'mission_id' => $mission->id,
                'mission_version' => 4,
                'pts_earned' => 100,
            ]);
            $history = $progress->refresh()->getRawOriginal();
            $before = $mission->refresh()->getRawOriginal();
            $publishedSource = $matches[1]."\n\n".$localSource;
            $payload = [
                'title' => $mission->title,
                'description' => $mission->description,
                'difficulty' => $mission->difficulty,
                'points' => $mission->points,
                'order_num' => $mission->order_num,
                'section_id' => $mission->section_id,
                'hints' => $mission->hints,
                'broken_code' => $publishedSource,
                'target_html' => $mission->target_html,
            ];
            $url = route('admin.courses.missions.update', [$mission->course, $mission]);

            foreach ([$teacher, $operator] as $unauthorized) {
                $this->actingAs($unauthorized)->put($url, $payload)->assertForbidden();
                $this->assertSame($before, $mission->refresh()->getRawOriginal());
            }

            $this->actingAs($admin)->put($url, $payload)
                ->assertRedirect(route('admin.courses.missions', $mission->course));
            $after = $mission->refresh()->getRawOriginal();
            $this->assertSame($publishedSource, $mission->broken_code);
            $this->assertSame(5, $mission->version);
            unset($before['broken_code'], $before['version'], $before['updated_at'], $after['broken_code'], $after['version'], $after['updated_at']);
            $this->assertSame($before, $after);
            $this->assertSame($history, $progress->refresh()->getRawOriginal());
            $this->assertDatabaseHas('the404_admin_audit', [
                'admin_user_id' => $admin->id,
                'action' => 'mission.update',
                'target_id' => $mission->id,
                'result' => 'success',
            ]);
            $audit = AdminAudit::query()->where('target_id', $mission->id)->sole();
            $this->assertStringContainsString('broken_code', $audit->summary);
            $this->assertStringContainsString('version 4 → 5', $audit->summary);
            $auditCount = AdminAudit::query()->count();

            $this->put($url, $payload)->assertRedirect(route('admin.courses.missions', $mission->course));
            $this->assertSame(5, $mission->refresh()->version);
            $this->assertSame($history, $progress->refresh()->getRawOriginal());
            $this->assertSame($auditCount, AdminAudit::query()->count());
        }

        $this->assertSame(9, AdminAudit::query()->count());
    }

    public function test_real_workspaces_pass_original_curriculum_setup_without_exposing_grading_secrets(): void
    {
        $this->seed(CurriculumContentSeeder::class);
        $mission = Mission::query()->where('title', 'DOM_Node_Activation')->firstOrFail();
        Course::query()->where('order_num', '<', $mission->course->order_num)->update(['status' => 'locked']);
        $student = User::factory()->create();
        $original = (string) Js::from($mission->broken_code);

        $this->actingAs($student)->get(route('mission.show', $mission))
            ->assertSee('var initial = '.$original, false)
            ->assertSee('type, initial)', false)
            ->assertDontSee((string) Js::from($mission->solution_code), false)
            ->assertDontSee($mission->validate_rule, false);
        $this->get(route('mission.challenge', $mission))
            ->assertSee('var starter = '.$original, false)
            ->assertSee('type, starter)', false)
            ->assertDontSee((string) Js::from($mission->solution_code), false)
            ->assertDontSee($mission->validate_rule, false);
    }
}
