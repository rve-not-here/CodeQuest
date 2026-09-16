<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_timeline_requires_authentication(): void
    {
        $this->get(route('timeline'))->assertRedirect(route('login'));
    }

    public function test_the_timeline_shows_the_empty_state_for_a_fresh_student(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('timeline'))
            ->assertOk()
            ->assertSee('NO LEARNING EVENTS');
    }

    public function test_the_timeline_is_scoped_to_the_authenticated_student(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();

        Activity::query()->create([
            'user_id' => $other->id,
            'type' => 'mission_completed',
            'message' => 'Mission completed: Another students secret',
            'pts' => 10,
        ]);

        $this->actingAs($student)
            ->get(route('timeline'))
            ->assertOk()
            ->assertDontSee('Another students secret');
    }

    public function test_login_and_logout_activity_are_not_displayed(): void
    {
        $user = User::factory()->create();

        Activity::query()->create(['user_id' => $user->id, 'type' => 'login', 'message' => 'Operator x logged in']);
        Activity::query()->create(['user_id' => $user->id, 'type' => 'logout', 'message' => 'Operator x logged out']);

        $this->actingAs($user)
            ->get(route('timeline'))
            ->assertOk()
            ->assertDontSee('logged in')
            ->assertDontSee('logged out');
    }

    public function test_mission_completion_is_displayed(): void
    {
        $user = User::factory()->create();

        Activity::query()->create([
            'user_id' => $user->id,
            'type' => 'mission_completed',
            'message' => 'Mission completed: Alpha Protocol',
            'pts' => 10,
        ]);

        $this->actingAs($user)
            ->get(route('timeline'))
            ->assertOk()
            ->assertSee('Mission completed: Alpha Protocol');
    }

    public function test_wrong_submission_is_displayed(): void
    {
        $user = User::factory()->create();

        Activity::query()->create([
            'user_id' => $user->id,
            'type' => 'wrong_submission',
            'message' => 'Wrong submission on mission: Alpha',
        ]);

        $this->actingAs($user)
            ->get(route('timeline'))
            ->assertOk()
            ->assertSee('Wrong submission on mission: Alpha');
    }

    public function test_ledger_hint_spend_events_are_displayed(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        XpTransaction::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => -5,
            'type' => 'hint_used',
            'description' => 'Hint 1 on mission: Alpha',
        ]);

        $this->actingAs($user)
            ->get(route('timeline'))
            ->assertOk()
            ->assertSee('Hint 1 on mission: Alpha');
    }

    public function test_assessment_results_are_displayed(): void
    {
        $user = User::factory()->create();
        $assessment = Assessment::factory()->create(['title' => 'The Final Directive']);

        AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => 'passed',
            'score' => 75,
            'passed_at' => now(),
            'submitted_at' => now(),
        ]);
        AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => 'failed',
            'score' => 30,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('timeline'))
            ->assertOk()
            ->assertSee('Boss Challenge passed: The Final Directive (score 75)')
            ->assertSee('Boss Challenge failed: The Final Directive (score 30)');
    }

    public function test_section_completion_beat_is_displayed(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['order_num' => 1]);
        $section = Section::factory()->create(['course_id' => $course->id, 'order_num' => 1]);

        for ($order = 1; $order <= 2; $order++) {
            $mission = Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $order,
            ]);
            $this->complete($user, $mission);
        }

        $this->actingAs($user)
            ->get(route('timeline'))
            ->assertOk()
            ->assertSee('Section complete: '.$section->title);
    }

    public function test_the_timeline_shows_the_current_xp_balance(): void
    {
        $user = User::factory()->create();
        $mission = Mission::factory()->create();

        XpTransaction::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => 40,
            'type' => 'mission_completed',
            'description' => 'Mission completed: Alpha',
        ]);

        $this->actingAs($user)
            ->get(route('timeline'))
            ->assertOk()
            ->assertSee('XP 40');
    }

    public function test_attempts_to_view_another_students_timeline_fail_via_any_parameter(): void
    {
        $attacker = User::factory()->create();
        $victim = User::factory()->create();

        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $parameter) {
            $this->actingAs($attacker)
                ->get(route('timeline', [$parameter => $victim->id]))
                ->assertForbidden();
        }
    }

    private function complete(User $user, Mission $mission): void
    {
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => now(),
        ]);
    }
}
