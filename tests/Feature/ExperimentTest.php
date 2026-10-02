<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExperimentTest extends TestCase
{
    use RefreshDatabase;

    public function test_experiment_is_available_before_required_checks_and_cannot_submit_academic_state(): void
    {
        $student = User::factory()->create();
        $mission = Mission::factory()->create(['broken_code' => '<h1>Try this</h1>', 'solution_code' => 'PRIVATE SOLUTION']);
        KnowledgeCheck::factory()->required()->create(['mission_id' => $mission->id]);

        $this->actingAs($student)->get(route('mission.show', $mission))->assertSee(route('mission.experiment', $mission));
        $this->get(route('mission.experiment', $mission))
            ->assertOk()->assertSee('Ungraded experiment')->assertSee('RESET EXAMPLE')
            ->assertDontSee('PRIVATE SOLUTION')->assertDontSee('SUBMIT CHALLENGE');
        $this->post(route('mission.experiment', $mission), ['code' => 'valid', 'passed' => true, 'xp' => 10000])->assertStatus(405);
        $this->get(route('mission.challenge', $mission))->assertRedirect(route('mission.show', $mission));
        foreach (['the404_progress', 'the404_xp_transactions', 'the404_assessment_attempts', 'the404_knowledge_check_attempts', 'the404_user_achievements', 'the404_mission_drafts'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_experiment_preserves_authentication_role_and_course_gates(): void
    {
        $mission = Mission::factory()->create();
        $url = route('mission.experiment', $mission);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'teacher']))->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->get($url)->assertOk();
        $mission->course->update(['status' => 'locked']);
        $this->get($url)->assertForbidden();
        $mission->course->update(['status' => 'active']);
        $earlier = Course::factory()->create(['order_num' => $mission->course->order_num - 1]);
        Mission::factory()->create(['course_id' => $earlier->id]);
        $this->get($url)->assertForbidden();
    }
}
