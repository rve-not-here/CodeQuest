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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_requires_authentication(): void
    {
        $this->get(route('activity'))->assertRedirect(route('login'));
    }

    public function test_activity_is_restricted_to_teachers(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('activity'))
            ->assertForbidden();
    }

    public function test_the_feed_shows_learning_events_across_students_with_attribution(): void
    {
        $oldest = $this->student();
        $newest = $this->student();

        $this->activity($oldest, 'mission_completed', 'Mission completed: Alpha-606', 10, now()->subMinutes(10));
        $this->xp($newest, 'hint_used', 'Hint 1 on mission: Gamma-606', -5, now()->subMinutes(5));

        $this->actingAs($this->teacher())
            ->get(route('activity'))
            ->assertOk()
            ->assertSee('Mission completed: Alpha-606')
            ->assertSee('Hint 1 on mission: Gamma-606')
            ->assertSee($oldest->username)
            ->assertSee($newest->username)
            ->assertSee('-5 XP')
            ->assertSeeInOrder(['Hint 1 on mission: Gamma-606', 'Mission completed: Alpha-606']);
    }

    public function test_trivial_boundary_events_are_not_displayed(): void
    {
        $student = $this->student();

        $this->activity($student, 'login', 'Operator logged in', 0, now());
        $this->activity($student, 'logout', 'Operator logged out', 0, now());

        $this->actingAs($this->teacher())
            ->get(route('activity'))
            ->assertOk()
            ->assertDontSee('logged in')
            ->assertDontSee('logged out');
    }

    public function test_challenge_results_and_the_award_beat_are_displayed_with_score(): void
    {
        $passed = $this->student();
        $failed = $this->student();
        $assessment = $this->assessment('The Final Directive');

        $this->attempt($passed, $assessment, 'passed', 75, now()->subMinutes(5));
        $this->xp($passed, 'assessment_completed', 'Boss Challenge award: The Final Directive', 100, now()->subMinutes(5));
        $this->attempt($failed, $assessment, 'failed', 30, now()->subMinutes(3));

        $this->actingAs($this->teacher())
            ->get(route('activity'))
            ->assertOk()
            ->assertSee('Boss Challenge passed: The Final Directive (score 75)')
            ->assertSee('Boss Challenge failed: The Final Directive (score 30)')
            ->assertSee('Boss Challenge award: The Final Directive')
            ->assertSee('+100 XP');
    }

    public function test_section_completion_beats_are_displayed_with_the_student(): void
    {
        $student = $this->student();
        $course = Course::factory()->create(['order_num' => 1]);
        $section = $this->completedSection($student, $course, 'Stellar Drift');

        $this->actingAs($this->teacher())
            ->get(route('activity'))
            ->assertOk()
            ->assertSee('Section complete: Stellar Drift')
            ->assertSee($student->username);

        $this->assertTrue($section->exists);
    }

    public function test_the_student_filter_narrows_the_feed(): void
    {
        $student = $this->student();
        $other = $this->student();

        $this->activity($student, 'mission_completed', 'Mission completed: Narrowed-606', 10, now());
        $this->activity($other, 'mission_completed', 'Mission completed: Hidden-606', 10, now());

        $this->actingAs($this->teacher())
            ->get(route('activity', ['student' => $student->id]))
            ->assertOk()
            ->assertSee('Mission completed: Narrowed-606')
            ->assertDontSee('Mission completed: Hidden-606');
    }

    public function test_the_event_type_filter_narrows_the_feed(): void
    {
        $student = $this->student();

        $this->activity($student, 'mission_completed', 'Mission completed: Typed-606', 10, now());
        $this->xp($student, 'solution_revealed', 'Solution revealed on mission: Hidden-606', -15, now());

        $this->actingAs($this->teacher())
            ->get(route('activity', ['type' => 'mission_completed']))
            ->assertOk()
            ->assertSee('Mission completed: Typed-606')
            ->assertDontSee('Hidden-606');
    }

    public function test_the_event_type_filter_can_select_assessment_verdicts(): void
    {
        $student = $this->student();
        $assessment = $this->assessment('Verdict Filter');

        $this->attempt($student, $assessment, 'passed', 80, now());

        $this->actingAs($this->teacher())
            ->get(route('activity', ['type' => 'assessment_passed']))
            ->assertOk()
            ->assertSee('Boss Challenge passed: Verdict Filter (score 80)');

        $this->actingAs($this->teacher())
            ->get(route('activity', ['type' => 'assessment_failed']))
            ->assertOk()
            ->assertDontSee('Boss Challenge passed: Verdict Filter');
    }

    public function test_the_date_range_filter_is_inclusive(): void
    {
        $student = $this->student();
        $course = Course::factory()->create(['order_num' => 1]);

        $this->activity($student, 'mission_completed', 'Mission completed: Two Days-606', 10, now()->subDays(2)->setTime(10, 0));
        $this->activity($student, 'mission_completed', 'Mission completed: Three Days-606', 10, now()->subDays(3)->setTime(10, 0));
        $this->completedSection($student, $course, 'Ancient Section', now()->subDays(20));

        $day = now()->subDays(2)->format('Y-m-d');

        $this->actingAs($this->teacher())
            ->get(route('activity', ['from' => $day, 'to' => $day]))
            ->assertOk()
            ->assertSee('Mission completed: Two Days-606')
            ->assertDontSee('Mission completed: Three Days-606')
            ->assertDontSee('Section complete: Ancient Section');
    }

    public function test_the_course_filter_applies_to_course_attributable_events(): void
    {
        $student = $this->student();

        // A passed attempt on course A's assessment.
        $courseA = $this->course('Alpha Directives');
        $attemptA = $this->attempt($student, $this->assessment('Alpha Final', $courseA), 'passed', 75, now()->subMinutes(4));

        // A failed attempt on course B's assessment.
        $courseB = $this->course('Bravo Directives');
        $this->attempt($student, $this->assessment('Bravo Final', $courseB), 'failed', 30, now()->subMinutes(2));

        // Mission-completion activity rows carry no course link and are not
        // attributable under a course filter.
        $this->activity($student, 'mission_completed', 'Mission completed: Unattributable-606', 10, now()->subMinutes(1));

        $this->actingAs($this->teacher())
            ->get(route('activity', ['course' => $courseA->id]))
            ->assertOk()
            ->assertSee('Boss Challenge passed: Alpha Final (score 75)')
            ->assertDontSee('Bravo Final')
            ->assertDontSee('Unattributable-606');

        $this->assertTrue($attemptA->exists);

        $this->actingAs($this->teacher())
            ->get(route('activity', ['course' => $courseB->id]))
            ->assertOk()
            ->assertSee('Boss Challenge failed: Bravo Final (score 30)')
            ->assertDontSee('Alpha Final');
    }

    public function test_the_feed_paginates_the_filtered_events(): void
    {
        $student = $this->student();

        for ($n = 1; $n <= 35; $n++) {
            $this->activity($student, 'mission_completed', sprintf('EVENT %02d', $n), 10, now()->subMinutes($n));
        }

        $this->actingAs($this->teacher())
            ->get(route('activity'))
            ->assertOk()
            ->assertSee('EVENT 01')
            ->assertSee('EVENT 30')
            ->assertDontSee('EVENT 31')
            ->assertSee('SHOWING PAGE 1 OF 2');

        $this->actingAs($this->teacher())
            ->get(route('activity', ['page' => 2]))
            ->assertOk()
            ->assertSee('EVENT 31')
            ->assertDontSee('EVENT 01')
            ->assertSee('SHOWING PAGE 2 OF 2');
    }

    public function test_invalid_filter_values_are_rejected_server_side(): void
    {
        $this->actingAs($this->teacher())
            ->get(route('activity', ['type' => 'not_a_type']))
            ->assertSessionHasErrors('type');

        $this->actingAs($this->teacher())
            ->get(route('activity', ['course' => 999999]))
            ->assertSessionHasErrors('course');

        $this->actingAs($this->teacher())
            ->get(route('activity', ['student' => 999999]))
            ->assertSessionHasErrors('student');

        $this->actingAs($this->teacher())
            ->get(route('activity', ['from' => '2026-09-10', 'to' => '2026-09-01']))
            ->assertSessionHasErrors('from');

        $this->actingAs($this->teacher())
            ->get(route('activity', ['from' => 'not-a-date']))
            ->assertSessionHasErrors('from');
    }

    public function test_a_client_cannot_widen_the_window_beyond_the_maximum_span(): void
    {
        // The feed's in-memory slice is bounded by its window (§44/§45), so
        // the window itself is capped: a wider client-supplied span is
        // rejected before any computation.
        $this->actingAs($this->teacher())
            ->get(route('activity', ['from' => now()->subDays(91)->toDateString(), 'to' => now()->toDateString()]))
            ->assertSessionHasErrors('from');
    }

    public function test_the_window_boundary_at_the_maximum_span_is_accepted(): void
    {
        $this->actingAs($this->teacher())
            ->get(route('activity', ['from' => now()->subDays(90)->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk();
    }

    public function test_user_scoping_probe_spellings_are_rejected_but_student_filter_is_allowed(): void
    {
        $student = $this->student();

        foreach (['user_id', 'userId', 'user', 'owner'] as $parameter) {
            $this->actingAs($this->teacher())
                ->get(route('activity', [$parameter => $student->id]))
                ->assertForbidden();
        }

        $this->actingAs($this->teacher())
            ->get(route('activity', ['student' => $student->id]))
            ->assertOk();
    }

    public function test_the_feed_shows_the_empty_state_for_a_fresh_fleet(): void
    {
        $this->actingAs($this->teacher())
            ->get(route('activity'))
            ->assertOk()
            ->assertSee('NO ACTIVITY');
    }

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function student(): User
    {
        return User::factory()->create();
    }

    private function activity(User $user, string $type, string $message, int $pts, Carbon $at): Activity
    {
        $activity = new Activity([
            'user_id' => $user->id,
            'type' => $type,
            'message' => $message,
            'pts' => $pts,
        ]);
        $activity->forceFill(['created_at' => $at])->save();

        return $activity->refresh();
    }

    private function xp(User $user, string $type, string $description, int $amount, Carbon $at): XpTransaction
    {
        $txn = new XpTransaction([
            'user_id' => $user->id,
            'type' => $type,
            'description' => $description,
            'amount' => $amount,
        ]);
        $txn->forceFill(['created_at' => $at])->save();

        return $txn->refresh();
    }

    private function course(string $name): Course
    {
        return Course::factory()->create(['name' => $name]);
    }

    private function assessment(string $title, ?Course $course = null): Assessment
    {
        return Assessment::factory()->create([
            'title' => $title,
            'course_id' => $course?->id ?? Course::factory(),
        ]);
    }

    private function attempt(User $user, Assessment $assessment, string $status, int $score, Carbon $at): AssessmentAttempt
    {
        return AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => $status,
            'score' => $score,
            'passed_at' => $status === 'passed' ? $at : null,
            'submitted_at' => $at,
            'created_at' => $at,
        ]);
    }

    private function completedSection(User $user, Course $course, string $title, ?Carbon $at = null): Section
    {
        $at ??= now();

        $section = Section::factory()->create(['course_id' => $course->id, 'title' => $title]);

        for ($order = 1; $order <= 2; $order++) {
            $mission = Mission::factory()->create([
                'course_id' => $course->id,
                'section_id' => $section->id,
                'order_num' => $order,
            ]);

            Progress::query()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'pts_earned' => 10,
                'completed_at' => $at,
                'created_at' => $at,
            ]);
        }

        return $section;
    }
}
