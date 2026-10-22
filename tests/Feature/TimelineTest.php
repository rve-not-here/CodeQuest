<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\XpTransaction;
use App\Services\TimelineService;
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
            ->assertSee('No learning events yet');
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

    public function test_timeline_groups_dates_without_merging_attempts(): void
    {
        $user = User::factory()->create();
        foreach ([['2026-10-01 09:00:00', 'Earlier attempt'], ['2026-10-02 10:00:00', 'Later attempt'], ['2026-10-02 11:00:00', 'Latest attempt']] as [$at, $message]) {
            $activity = new Activity(['user_id' => $user->id, 'type' => 'wrong_submission', 'message' => $message]);
            $activity->forceFill(['created_at' => $at])->save();
        }

        $this->actingAs($user)->get(route('timeline'))
            ->assertOk()
            ->assertSeeInOrder(['October 2, 2026', 'Latest attempt', 'Later attempt', 'October 1, 2026', 'Earlier attempt']);
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

    public function test_achievement_unlocks_are_displayed_in_the_timeline(): void
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create(['name' => 'First Steps']);
        UserAchievement::factory()->create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);

        $this->actingAs($user)
            ->get(route('timeline'))
            ->assertOk()
            ->assertSee('Achievement unlocked: First Steps');
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

    public function test_the_timeline_discloses_the_fifty_beat_cap_with_the_real_total(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 55) as $index) {
            Activity::query()->create([
                'user_id' => $user->id,
                'type' => 'mission_completed',
                'message' => "Mission completed: Beat {$index}",
                'pts' => 10,
            ]);
        }

        $this->actingAs($user)
            ->get(route('timeline'))
            ->assertOk()
            ->assertSee('Showing 1–50 of 55 entries')
            ->assertSee('Page 1 of 2');

        $this->actingAs($user)
            ->get(route('timeline', ['page' => 2]))
            ->assertOk()
            ->assertSee('Showing 51–55 of 55 entries')
            ->assertSee('Page 2 of 2');
    }

    public function test_the_wrong_submission_penalty_is_visible_on_the_timeline(): void
    {
        $user = User::factory()->create();

        Activity::query()->create([
            'user_id' => $user->id,
            'type' => 'wrong_submission',
            'message' => 'Wrong submission on challenge: Penalty Probe',
            'pts' => -10,
        ]);

        $this->actingAs($user)
            ->get(route('timeline'))
            ->assertOk()
            ->assertSee('Wrong submission on challenge: Penalty Probe')
            ->assertSee('-10 XP');
    }

    public function test_infrastructure_grading_failures_are_not_learning_beats(): void
    {
        $user = User::factory()->create();

        Activity::query()->create([
            'user_id' => $user->id,
            'type' => 'grading_unavailable',
            'message' => 'Behavioral grading unavailable on challenge: Infrastructure Noise',
        ]);

        $this->actingAs($user)
            ->get(route('timeline'))
            ->assertOk()
            ->assertDontSee('Infrastructure Noise');
    }

    public function test_same_second_beats_are_ordered_by_id_not_build_order(): void
    {
        $user = User::factory()->create();
        $at = now()->startOfSecond();

        $first = Activity::query()->create([
            'user_id' => $user->id,
            'type' => 'wrong_submission',
            'message' => 'Wrong submission: first written',
            'pts' => -10,
        ]);
        $second = Activity::query()->create([
            'user_id' => $user->id,
            'type' => 'wrong_submission',
            'message' => 'Wrong submission: second written',
            'pts' => -10,
        ]);

        // Force an identical timestamp; only the id tiebreak can order them.
        Activity::query()->whereKey([$first->id, $second->id])->update(['created_at' => $at]);

        $events = app(TimelineService::class)->events($user);

        $this->assertSame(
            ['Wrong submission: second written', 'Wrong submission: first written'],
            $events->pluck('label')->all(),
        );
    }

    public function test_the_scoped_knowledge_check_beat_keeps_its_score(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'active']);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $mission = Mission::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        $check = KnowledgeCheck::factory()->create(['mission_id' => $mission->id]);

        KnowledgeCheckAttempt::factory()->submitted(score: 3, total: 4)->create([
            'knowledge_check_id' => $check->id,
            'user_id' => $user->id,
        ]);

        $events = app(TimelineService::class)->events($user, 50, collect([$course->id]));
        $beat = $events->firstWhere('type', 'knowledge_check_completed');

        $this->assertNotNull($beat);
        $this->assertStringContainsString('(3/4, 75%)', $beat['label']);
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
