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

    public function test_workspace_offers_named_keyboard_separators_and_pane_controls_without_changing_a_draft(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create();
        $draft = MissionDraft::factory()->create(['user_id' => $student->id, 'mission_id' => $mission->id, 'code' => '<h1>Saved draft</h1>']);

        $this->actingAs($student)->get(route('mission.challenge', $mission))->assertOk()
            ->assertSee('aria-label="Resize brief pane"', false)
            ->assertSee('aria-label="Resize preview pane"', false)
            ->assertSee('role="separator" tabindex="0"', false)
            ->assertSee('data-pane-toggle="editor"', false)
            ->assertSee('Reset panes')
            ->assertSee('aria-label="Challenge actions"', false)
            ->assertSee('Task')->assertSee('Code')->assertSee('Output');
        $this->assertSame('<h1>Saved draft</h1>', $draft->fresh()->code);
        $this->assertDatabaseCount('the404_xp_transactions', 0);
    }
}
