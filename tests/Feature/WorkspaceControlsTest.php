<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_challenge_offers_tabbed_preview_and_keyboard_instructions_resize_without_changing_a_draft(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create();
        $draft = MissionDraft::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id, 'code' => '<h1>Saved draft</h1>']);

        $this->actingAs($student)->get(route('mission.challenge', $mission))->assertOk()
            ->assertSee('data-workspace-layout="tabs"', false)
            ->assertSee('role="tablist"', false)
            ->assertSee('aria-label="Resize instructions"', false)
            ->assertDontSee('Resize preview pane')
            ->assertSee('role="separator" tabindex="0"', false)
            ->assertSee('data-pane-toggle="editor"', false)
            ->assertSee('Reset panes')
            ->assertSee('aria-label="Challenge actions"', false)
            ->assertSee('Difficulty')->assertSee('Reward')->assertSee('Code')->assertSee('Preview')
            ->assertSee('data-challenge-confirm', false)
            ->assertDontSee('>Save draft</button>', false);
        $this->assertSame('<h1>Saved draft</h1>', $draft->fresh()->code);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }

    public function test_challenge_reads_authored_lesson_and_public_reference_without_exposing_internal_rules(): void
    {
        $student = User::factory()->create();
        $description = "Authored context <script>alert(1)</script>.\n\nAuthored task stays intact.";
        $mission = Mission::factory()->create([
            'description' => $description,
            'target_html' => '<h1>Public reference</h1>',
            'validate_rule' => 'validator-secret',
            'solution_code' => 'private-reference-solution',
        ]);

        $this->actingAs($student)->get(route('mission.challenge', $mission))->assertOk()
            ->assertSee($description)
            ->assertSee('<h1>Public reference</h1>')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('validator-secret')
            ->assertDontSee('private-reference-solution')
            ->assertDontSee('Submission feedback');

        $this->assertDatabaseCount('the404_progress', 0);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }
}
