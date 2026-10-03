<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\Notification;
use App\Models\Progress;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\StudentReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Student reminders (US-809): the student's own GET /dashboard lazily emits
 * two frequency-governed notifications — a DRAFT_REMINDER for a mission draft
 * left untouched for 3 then 7 days, and a single LEARNING_REMINDER for the
 * current course (stalled first, then an unlocked-but-unattempted challenge).
 * Both are change-driven, so daily dashboard visits while the situation is
 * unchanged produce exactly one row, not one per visit.
 */
class StudentReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stalled_current_course_reminds_exactly_once_across_a_week_of_daily_visits(): void
    {
        $base = $this->base();
        $course = $this->course('Boss', 1, 3);
        $missions = $this->missions($course);
        $student = $this->student('alice');

        $this->complete($student, $missions[0], $base->copy()->subDays(14));

        for ($day = 0; $day < 7; $day++) {
            $this->travelTo($base->copy()->addDays($day));
            $this->visit($student)->assertOk();

            $this->assertSame(
                1,
                $this->countRows($student, NotificationService::TYPE_LEARNING_REMINDER),
                "a second learning reminder appeared on day {$day}",
            );
        }

        $row = $this->latestRow($student, NotificationService::TYPE_LEARNING_REMINDER);

        $this->assertSame('learning_reminder', $row->type);
        $this->assertNull($row->dedupe_key);
        $this->assertSame('COURSE STALLED', $row->title);
        $this->assertSame('No new challenge on "Boss" in 14 days (1/3 challenges). Pick it back up.', $row->message);
        $this->assertEquals([
            'route' => 'learning-path',
            'params' => [],
            'course' => $course->id,
            'kind' => 'stalled',
        ], $row->data);
        $this->assertSame(route('learning-path'), app(NotificationService::class)->linkFor($row));

        $this->actingAs($student)
            ->get(route('notifications'))
            ->assertOk()
            ->assertSee('COURSE STALLED')
            ->assertSee(route('learning-path'));
    }

    public function test_the_learning_reminder_tracks_the_state_transition_from_stalled_to_challenge_ready(): void
    {
        $base = $this->base();
        $course = $this->course('Boss', 1, 3);
        $missions = $this->missions($course);
        $student = $this->student('alice');

        $this->complete($student, $missions[0], $base->copy()->subDays(14));

        $this->travelTo($base);
        $this->visit($student)->assertOk();
        $this->assertSame(1, $this->countRows($student, NotificationService::TYPE_LEARNING_REMINDER));

        // The student returns and finishes the course: the stalled snapshot
        // gives way to the challenge-ready snapshot, so a second reminder is
        // due — a genuine change, not a repeat of the same state.
        $this->complete($student, $missions[1], $base);
        $this->complete($student, $missions[2], $base);

        $this->visit($student)->assertOk();
        $this->assertSame(2, $this->countRows($student, NotificationService::TYPE_LEARNING_REMINDER));

        $ready = $this->latestRow($student, NotificationService::TYPE_LEARNING_REMINDER);
        $this->assertSame('CHALLENGE READY', $ready->title);
        $this->assertEquals([
            'route' => 'learning-path',
            'params' => [],
            'course' => $course->id,
            'kind' => 'assessment',
        ], $ready->data);

        // Attempting the challenge ends the reminder entirely; further visits
        // add nothing.
        $this->attempt($student, $course, 'failed', $base);

        $this->visit($student)->assertOk();
        $this->visit($student)->assertOk();

        $this->assertSame(2, $this->countRows($student, NotificationService::TYPE_LEARNING_REMINDER));
    }

    public function test_reminders_are_only_evaluated_on_the_dashboard(): void
    {
        $base = $this->base();
        $course = $this->course('Boss', 1, 3);
        $missions = $this->missions($course);
        $student = $this->student('alice');

        $this->complete($student, $missions[0], $base->copy()->subDays(14));

        $this->travelTo($base->copy()->subDays(4));
        MissionDraft::factory()->create([
            'user_id' => $student->id,
            'mission_id' => $missions[1]->id,
        ]);

        $this->travelTo($base);
        $this->actingAs($student)->get(route('learning-path'))->assertOk();

        $this->assertSame(0, $this->countRows($student, NotificationService::TYPE_LEARNING_REMINDER));
        $this->assertSame(0, $this->countRows($student, NotificationService::TYPE_DRAFT_REMINDER));
    }

    public function test_a_fresh_student_who_has_not_started_gets_no_learning_reminder(): void
    {
        $base = $this->base();
        $this->course('Boss', 1, 3);
        $student = $this->student('alice');

        $this->travelTo($base);
        $this->visit($student)->assertOk();

        $this->assertSame(0, $this->countRows($student, NotificationService::TYPE_LEARNING_REMINDER));
    }

    public function test_no_learning_reminder_for_a_sealed_course(): void
    {
        $base = $this->base();
        $student = $this->student('alice');

        foreach (['locked', 'draft'] as $order => $status) {
            $course = $this->course(ucfirst($status), $order + 1, 3, 70, $status);
            $this->complete($student, $this->missions($course)[0], $base->copy()->subDays(14));
        }

        $this->travelTo($base);
        $this->visit($student)->assertOk();

        $this->assertSame(0, $this->countRows($student, NotificationService::TYPE_LEARNING_REMINDER));
    }

    public function test_no_learning_reminder_for_a_zero_mission_course(): void
    {
        $base = $this->base();
        $this->course('Empty', 1, 0);
        $student = $this->student('alice');

        $this->travelTo($base);
        $this->visit($student)->assertOk();

        $this->assertSame(0, $this->countRows($student, NotificationService::TYPE_LEARNING_REMINDER));
    }

    public function test_no_learning_reminder_for_a_completed_course(): void
    {
        $base = $this->base();
        $course = $this->course('Boss', 1, 2);
        $student = $this->student('alice');

        foreach ($this->missions($course) as $mission) {
            $this->complete($student, $mission, $base->copy()->subDays(14));
        }

        $this->attempt($student, $course, 'passed', $base->copy()->subDay());

        $this->travelTo($base);
        $this->visit($student)->assertOk();

        $this->assertSame(0, $this->countRows($student, NotificationService::TYPE_LEARNING_REMINDER));
    }

    public function test_no_learning_reminder_once_the_challenge_has_been_attempted(): void
    {
        $base = $this->base();
        $course = $this->course('Boss', 1, 2);
        $student = $this->student('alice');

        foreach ($this->missions($course) as $mission) {
            $this->complete($student, $mission, $base);
        }

        $this->attempt($student, $course, 'failed', $base);

        $this->travelTo($base);
        $this->visit($student)->assertOk();

        $this->assertSame(0, $this->countRows($student, NotificationService::TYPE_LEARNING_REMINDER));
    }

    public function test_non_students_never_receive_reminders(): void
    {
        $base = $this->base();
        $course = $this->course('Boss', 1, 3);
        $missions = $this->missions($course);

        foreach (['teacher', 'admin', 'operator'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->complete($user, $missions[0], $base->copy()->subDays(14));

            $this->travelTo($base->copy()->subDays(4));
            MissionDraft::factory()->create([
                'user_id' => $user->id,
                'mission_id' => $missions[1]->id,
            ]);

            $this->travelTo($base);
            $this->visit($user)->assertForbidden();
            $this->assertSame(0, app(StudentReminderService::class)->syncFor($user));

            $this->assertSame(
                0,
                $this->countRows($user, NotificationService::TYPE_LEARNING_REMINDER),
                "a {$role} received a learning reminder",
            );
            $this->assertSame(
                0,
                $this->countRows($user, NotificationService::TYPE_DRAFT_REMINDER),
                "a {$role} received a draft reminder",
            );
        }
    }

    public function test_a_draft_reminds_at_three_days_then_again_at_seven_days(): void
    {
        $base = $this->base();
        $course = $this->course('Boss', 1, 2);
        $mission = $this->missions($course)[0];
        $student = $this->student('alice');

        $this->travelTo($base->copy()->subDays(4));
        MissionDraft::factory()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
        ]);

        $this->travelTo($base);
        $this->visit($student)->assertOk();
        $this->assertSame(1, $this->countRows($student, NotificationService::TYPE_DRAFT_REMINDER));

        $first = $this->latestRow($student, NotificationService::TYPE_DRAFT_REMINDER);

        $this->assertSame('draft_reminder', $first->type);
        $this->assertNull($first->dedupe_key);
        $this->assertSame('CHALLENGE DRAFT AWAITING', $first->title);
        $this->assertSame('Your saved draft for "Mission Boss 1" is waiting (4 days). Pick it back up.', $first->message);
        $this->assertEquals([
            'route' => 'mission.show',
            'params' => ['mission' => $mission->id],
            'mission' => $mission->id,
            'stage' => 3,
        ], $first->data);
        $this->assertSame(route('mission.show', $mission), app(NotificationService::class)->linkFor($first));

        // Still inside the first band a couple of days later: no new row.
        $this->travelTo($base->copy()->addDays(2));
        $this->visit($student)->assertOk();
        $this->assertSame(1, $this->countRows($student, NotificationService::TYPE_DRAFT_REMINDER));

        // Past the seven-day threshold the final stage comes due exactly once.
        $this->travelTo($base->copy()->addDays(4));
        $this->visit($student)->assertOk();
        $this->assertSame(2, $this->countRows($student, NotificationService::TYPE_DRAFT_REMINDER));

        $second = $this->latestRow($student, NotificationService::TYPE_DRAFT_REMINDER);
        $this->assertSame('Your draft for "Mission Boss 1" has been untouched for 8 days. Return before it goes cold.', $second->message);
        $this->assertSame(7, $second->data['stage']);

        // Long after the final stage: still exactly two rows.
        $this->travelTo($base->copy()->addDays(10));
        $this->visit($student)->assertOk();
        $this->assertSame(2, $this->countRows($student, NotificationService::TYPE_DRAFT_REMINDER));

        $this->actingAs($student)
            ->get(route('notifications'))
            ->assertOk()
            ->assertSee('CHALLENGE DRAFT AWAITING')
            ->assertSee(route('mission.show', $mission));
    }

    public function test_a_fresh_draft_never_reminds(): void
    {
        $base = $this->base();
        $course = $this->course('Boss', 1, 2);
        $student = $this->student('alice');

        $this->travelTo($base);
        MissionDraft::factory()->create([
            'user_id' => $student->id,
            'mission_id' => $this->missions($course)[0]->id,
        ]);

        $this->visit($student)->assertOk();

        $this->assertSame(0, $this->countRows($student, NotificationService::TYPE_DRAFT_REMINDER));
    }

    public function test_a_draft_for_a_completed_mission_never_reminds(): void
    {
        $base = $this->base();
        $course = $this->course('Boss', 1, 3);
        $mission = $this->missions($course)[0];
        $student = $this->student('alice');

        $this->complete($student, $mission, $base);

        $this->travelTo($base->copy()->subDays(4));
        MissionDraft::factory()->create([
            'user_id' => $student->id,
            'mission_id' => $mission->id,
        ]);

        $this->travelTo($base);
        $this->visit($student)->assertOk();

        $this->assertSame(0, $this->countRows($student, NotificationService::TYPE_DRAFT_REMINDER));
    }

    public function test_a_draft_on_a_sealed_course_never_reminds(): void
    {
        $base = $this->base();
        $course = $this->course('Sealed', 1, 2, 70, 'locked');
        $student = $this->student('alice');

        $this->travelTo($base->copy()->subDays(4));
        MissionDraft::factory()->create([
            'user_id' => $student->id,
            'mission_id' => $this->missions($course)[0]->id,
        ]);

        $this->travelTo($base);
        $this->visit($student)->assertOk();

        $this->assertSame(0, $this->countRows($student, NotificationService::TYPE_DRAFT_REMINDER));
    }

    public function test_multiple_stale_drafts_each_remind_once(): void
    {
        $base = $this->base();
        $course = $this->course('Boss', 1, 3);
        $missions = $this->missions($course);
        $student = $this->student('alice');

        $this->travelTo($base->copy()->subDays(5));
        MissionDraft::factory()->create(['user_id' => $student->id, 'mission_id' => $missions[0]->id]);
        MissionDraft::factory()->create(['user_id' => $student->id, 'mission_id' => $missions[1]->id]);

        $this->travelTo($base);
        $this->visit($student)->assertOk();
        $this->assertSame(2, $this->countRows($student, NotificationService::TYPE_DRAFT_REMINDER));

        $this->visit($student)->assertOk();
        $this->assertSame(2, $this->countRows($student, NotificationService::TYPE_DRAFT_REMINDER));
    }

    // ── Fixture helpers ───────────────────────────────────────────────

    /**
     * A deterministic moment six hours past start-of-day; every relative
     * timestamp in this suite hangs off it so the calendar-day arithmetic is
     * exact regardless of when the suite runs.
     */
    private function base(): Carbon
    {
        return now()->startOfDay()->addHours(6);
    }

    private function student(string $username): User
    {
        return User::factory()->create(['username' => $username]);
    }

    private function course(string $name, int $order, int $missionCount, int $passingScore = 70, string $status = 'active'): Course
    {
        $course = Course::factory()->create([
            'slug' => Str::slug($name).'-'.$order,
            'name' => $name,
            'order_num' => $order,
            'status' => $status,
        ]);

        for ($i = 1; $i <= $missionCount; $i++) {
            Mission::factory()->create([
                'course_id' => $course->id,
                'order_num' => $i,
                'title' => "Mission {$name} {$i}",
            ]);
        }

        if ($missionCount > 0) {
            Assessment::factory()->create([
                'course_id' => $course->id,
                'title' => "{$name} Boss Challenge",
                'passing_score' => $passingScore,
                'status' => 'active',
            ]);
        }

        return $course->load('assessment');
    }

    /**
     * @return array<int, Mission>
     */
    private function missions(Course $course): array
    {
        return $course->missions()->orderBy('order_num')->get()->all();
    }

    private function complete(User $user, Mission $mission, ?Carbon $at = null): void
    {
        Progress::query()->create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'pts_earned' => 10,
            'completed_at' => $at ?? now(),
        ]);
    }

    private function attempt(User $user, Course $course, string $status, ?Carbon $at = null): void
    {
        $assessment = $course->assessment;
        $this->assertNotNull($assessment);

        AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => $status,
            'score' => $status === 'passed' ? 100 : 40,
            'passed_at' => $status === 'passed' ? ($at ?? now()) : null,
            'submitted_at' => $at ?? now(),
            'created_at' => $at ?? now(),
        ]);
    }

    private function visit(User $user): TestResponse
    {
        return $this->actingAs($user)->get(route('dashboard'));
    }

    private function countRows(User $user, string $type): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->count();
    }

    private function latestRow(User $user, string $type): Notification
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->firstOrFail();
    }
}
