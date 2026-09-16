<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AssessmentService;
use App\Services\AttentionService;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_needs_attention_requires_authentication(): void
    {
        $this->get(route('needs-attention'))->assertRedirect(route('login'));
    }

    public function test_needs_attention_is_restricted_to_teachers(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('needs-attention'))
            ->assertForbidden();
    }

    public function test_needs_attention_rejects_user_scoping_probes(): void
    {
        foreach (['user_id', 'userId', 'user', 'student', 'owner'] as $parameter) {
            $this->actingAs($this->teacher())
                ->get(route('needs-attention', [$parameter => 1]))
                ->assertForbidden();
        }
    }

    public function test_boss_fail_fires_on_a_failed_attempt_on_the_current_course(): void
    {
        $course = $this->course('Boss Course', 1, 2, 100);
        $student = $this->student('alice');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 40, $this->daysAgo(1));

        $row = $this->rowFor($student);
        $this->assertSame(['boss_fail'], $row['signals']);
        $this->assertSame('boss_fail', $row['primary']);
        $this->assertSame('Boss Challenge failed (score 40/100, attempt 1)', $row['reasons']['boss_fail']);
    }

    public function test_boss_fail_does_not_fire_when_the_latest_attempt_is_not_failed(): void
    {
        $course = $this->course('Boss Course', 1, 2, 100);
        $student = $this->student('alice');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 40, $this->daysAgo(5));
        AssessmentAttempt::factory()->create([
            'user_id' => $student->id,
            'assessment_id' => $course->assessment->id,
            'status' => 'submitted',
            'score' => null,
            'submitted_at' => $this->daysAgo(1),
        ]);

        $this->assertTrue($this->rows()->doesntContain(fn (array $row): bool => $row['student']->id === $student->id));
    }

    public function test_signals_fire_on_the_current_course_after_an_earlier_course_was_passed(): void
    {
        $first = $this->course('Alpha', 1, 2, 100);
        $second = $this->course('Beta', 2, 2, 70);
        $student = $this->student('mover');

        $this->completeAll($student, $this->missions($first));
        $this->attempt($student, $first->assessment, 'passed', 80, $this->daysAgo(60));

        $this->completeAll($student, $this->missions($second));
        $this->attempt($student, $second->assessment, 'failed', 40, $this->daysAgo(5));
        $this->attempt($student, $second->assessment, 'failed', 40, $this->daysAgo(1));

        $row = $this->rowFor($student);
        $this->assertNotNull($row);
        $this->assertSame($second->id, $row['current_course']->id);
        $this->assertContains('boss_fail', $row['signals']);
        $this->assertContains('repeat_fail', $row['signals']);
        $this->assertContains('low_performance', $row['signals']);
        $this->assertSame('Failed the Beta challenge 2 times', $row['reasons']['repeat_fail']);
        $this->assertSame('Average score 40/70 across 2 attempts', $row['reasons']['low_performance']);
    }

    public function test_repeat_fail_triggers_at_two_failed_attempts_not_one(): void
    {
        $course = $this->course('R', 1, 2, 100);
        $single = $this->student('one_fail');
        $double = $this->student('two_fails');

        $this->completeAll($single, $this->missions($course));
        $this->attempt($single, $course->assessment, 'failed', 50, $this->daysAgo(30));

        $this->completeAll($double, $this->missions($course));
        $this->attempt($double, $course->assessment, 'failed', 80, $this->daysAgo(30));
        $this->attempt($double, $course->assessment, 'failed', 90, $this->daysAgo(1));

        $singleRow = $this->rowFor($single);
        $this->assertSame(['boss_fail'], $singleRow['signals']);
        $this->assertArrayNotHasKey('repeat_fail', $singleRow['reasons']);

        $doubleRow = $this->rowFor($double);
        $this->assertContains('repeat_fail', $doubleRow['signals']);
        $this->assertSame('Failed the R challenge 2 times', $doubleRow['reasons']['repeat_fail']);
        $this->assertSame('Boss Challenge failed (score 90/100, attempt 2)', $doubleRow['reasons']['boss_fail']);
    }

    public function test_repeat_fail_fires_when_the_latest_attempt_was_not_a_failure(): void
    {
        $course = $this->course('R', 1, 2, 100);
        $student = $this->student('retried_then_resubmitted');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 80, $this->daysAgo(20));
        $this->attempt($student, $course->assessment, 'failed', 90, $this->daysAgo(10));
        AssessmentAttempt::factory()->create([
            'user_id' => $student->id,
            'assessment_id' => $course->assessment->id,
            'status' => 'submitted',
            'score' => null,
            'submitted_at' => $this->daysAgo(1),
        ]);

        $row = $this->rowFor($student);
        $this->assertSame(['repeat_fail'], $row['signals']);
        $this->assertSame('repeat_fail', $row['primary']);
    }

    public function test_low_performance_triggers_below_sixty_percent_of_passing(): void
    {
        $course = $this->course('L', 1, 2, 100);
        $student = $this->student('low');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 50, $this->daysAgo(10));
        $this->attempt($student, $course->assessment, 'failed', 50, $this->daysAgo(1));

        $row = $this->rowFor($student);
        $this->assertContains('low_performance', $row['signals']);
        $this->assertSame('Average score 50/100 across 2 attempts', $row['reasons']['low_performance']);
    }

    public function test_low_performance_does_not_trigger_at_exactly_sixty_percent(): void
    {
        $course = $this->course('L', 1, 2, 100);
        $student = $this->student('edge');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 60, $this->daysAgo(10));
        $this->attempt($student, $course->assessment, 'failed', 60, $this->daysAgo(1));

        $row = $this->rowFor($student);
        $this->assertNotContains('low_performance', $row['signals']);
        $this->assertContains('boss_fail', $row['signals']);
    }

    public function test_low_performance_needs_two_scored_attempts(): void
    {
        $course = $this->course('L', 1, 2, 100);
        $student = $this->student('one_attempt');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 50, $this->daysAgo(1));

        $row = $this->rowFor($student);
        $this->assertNotContains('low_performance', $row['signals']);
    }

    public function test_low_performance_ignores_unscored_submissions(): void
    {
        $course = $this->course('L', 1, 2, 100);
        $student = $this->student('null_score');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 50, $this->daysAgo(3));
        AssessmentAttempt::factory()->create([
            'user_id' => $student->id,
            'assessment_id' => $course->assessment->id,
            'status' => 'submitted',
            'score' => null,
            'submitted_at' => $this->daysAgo(1),
        ]);

        $this->assertTrue($this->rows()->doesntContain(fn (array $row): bool => $row['student']->id === $student->id));
    }

    public function test_low_performance_needs_a_real_pass_bar(): void
    {
        $course = $this->course('Zero Pass', 1, 2, 0);
        $student = $this->student('no_pass_bar');

        $this->completeAll($student, $this->missions($course));
        $this->attempt($student, $course->assessment, 'failed', 50, $this->daysAgo(10));
        $this->attempt($student, $course->assessment, 'failed', 50, $this->daysAgo(1));

        $row = $this->rowFor($student);
        $this->assertContains('boss_fail', $row['signals']);
        $this->assertNotContains('low_performance', $row['signals']);
    }

    public function test_stalled_triggers_at_fourteen_days_not_thirteen(): void
    {
        $course = $this->course('S', 1, 3, 100);
        $recent = $this->student('thirteen_days');
        $stale = $this->student('fourteen_days');

        $this->complete($recent, $this->missions($course)[0], $this->daysAgo(13));
        $this->complete($recent, $this->missions($course)[1], $this->daysAgo(13));

        $this->complete($stale, $this->missions($course)[0], $this->daysAgo(14));
        $this->complete($stale, $this->missions($course)[1], $this->daysAgo(14));

        $this->assertTrue($this->rows()->doesntContain(fn (array $row): bool => $row['student']->id === $recent->id));

        $staleRow = $this->rowFor($stale);
        $this->assertSame(['stalled'], $staleRow['signals']);
        $this->assertSame('No new mission on S in 14 days (2/3 missions)', $staleRow['reasons']['stalled']);
    }

    public function test_stalled_requires_partial_progress(): void
    {
        $course = $this->course('S', 1, 3, 100);
        $student = $this->student('all_done');

        $this->completeAll($student, $this->missions($course), $this->daysAgo(14));

        $this->assertTrue($this->rows()->doesntContain(fn (array $row): bool => $row['student']->id === $student->id));
    }

    public function test_inactivity_triggers_at_twenty_one_days_not_twenty_and_counts_any_event_source(): void
    {
        $course = $this->course('I', 1, 2, 100);

        $quiet = $this->student('twenty_days');
        $this->activity($quiet, 'login', 'Signed in', 0, $this->daysAgo(20));

        $gone = $this->student('twenty_one_days');
        $this->activity($gone, 'login', 'Signed in', 0, $this->daysAgo(21));

        $xpOnly = $this->student('xp_only');
        $this->xp($xpOnly, 'hint_used', 'Peeked at a hint', -10, $this->daysAgo(30));

        $progressOnly = $this->student('progress_only');
        $this->complete($progressOnly, $this->missions($course)[0], $this->daysAgo(25));

        $this->assertTrue($this->rows()->doesntContain(fn (array $row): bool => $row['student']->id === $quiet->id));

        $goneRow = $this->rowFor($gone);
        $this->assertSame(['inactive'], $goneRow['signals']);
        $this->assertSame('No activity in 21 days', $goneRow['reasons']['inactive']);

        $xpRow = $this->rowFor($xpOnly);
        $this->assertSame(['inactive'], $xpRow['signals']);

        $progressRow = $this->rowFor($progressOnly);
        $this->assertSame(['stalled', 'inactive'], $progressRow['signals']);
        $this->assertSame('No activity in 25 days', $progressRow['reasons']['inactive']);
    }

    public function test_inactivity_needs_at_least_one_event(): void
    {
        $this->course('I', 1, 2, 100);
        $student = $this->student('silent_ever', $this->daysAgo(30));

        $row = $this->rowFor($student);
        $this->assertSame(['never_started'], $row['signals']);
        $this->assertArrayNotHasKey('inactive', $row['reasons']);
    }

    public function test_never_started_triggers_at_fourteen_days_not_thirteen_and_requires_zero_progress(): void
    {
        $course = $this->course('N', 1, 2, 100);

        $fresh = $this->student('thirteen_days', $this->daysAgo(13));
        $never = $this->student('fourteen_days', $this->daysAgo(14));
        $engaged = $this->student('has_progress', $this->daysAgo(14));
        $this->complete($engaged, $this->missions($course)[0], $this->daysAgo(13));

        $engagedIds = [$fresh->id, $engaged->id];
        $this->assertTrue($this->rows()->doesntContain(fn (array $row): bool => in_array($row['student']->id, $engagedIds, true)));

        $neverRow = $this->rowFor($never);
        $this->assertSame(['never_started'], $neverRow['signals']);
        $this->assertSame('No progress since enrolment 14 days ago', $neverRow['reasons']['never_started']);
    }

    public function test_never_started_requires_a_course_with_missions(): void
    {
        $this->course('Zero', 1, 0, 100);
        $student = $this->student('no_course_to_start', $this->daysAgo(30));

        $this->assertTrue($this->rows()->doesntContain(fn (array $row): bool => $row['student']->id === $student->id));
    }

    public function test_rows_sort_by_primary_priority_then_stale_first(): void
    {
        $order = $this->course('Order', 1, 2, 100);

        $bossStale = $this->student('boss_stale');
        $this->attempt($bossStale, $order->assessment, 'failed', 40, $this->daysAgo(30));

        $bossRecent = $this->student('boss_recent');
        $this->attempt($bossRecent, $order->assessment, 'failed', 40, $this->daysAgo(5));

        $stalled = $this->student('stalled_only');
        $this->complete($stalled, $this->missions($order)[0], $this->daysAgo(20));

        $neverStarted = $this->student('never_started', $this->daysAgo(40));
        $this->activity($neverStarted, 'login', 'Signed in', 0, $this->daysAgo(30));

        $inactive = $this->student('inactive_only');
        $this->activity($inactive, 'login', 'Signed in', 0, $this->daysAgo(30));

        $rows = $this->rows();

        $this->assertSame(
            ['boss_stale', 'boss_recent', 'stalled_only', 'never_started', 'inactive_only'],
            $rows->pluck('student.username')->all(),
        );

        $byUsername = $rows->keyBy(fn (array $row): string => $row['student']->username);
        $this->assertSame('boss_fail', $byUsername['boss_stale']['primary']);
        $this->assertSame('boss_fail', $byUsername['boss_recent']['primary']);
        $this->assertSame('stalled', $byUsername['stalled_only']['primary']);
        $this->assertSame('never_started', $byUsername['never_started']['primary']);
        $this->assertSame('inactive', $byUsername['inactive_only']['primary']);

        $this->assertSame(['never_started', 'inactive'], $byUsername['never_started']['signals']);
    }

    public function test_the_signals_match_the_student_facing_state_services(): void
    {
        $this->buildFleet();

        $rowsByUser = $this->rows()->keyBy(fn (array $row): int => $row['student']->id);

        foreach (User::query()->where('role', 'student')->get() as $student) {
            $expected = $this->expectedFor($student);

            if ($expected['current_course'] === null) {
                $this->assertFalse(
                    $rowsByUser->has($student->id),
                    "Student {$student->username} should not be listed",
                );

                continue;
            }

            if ($expected['signals'] === []) {
                $this->assertFalse(
                    $rowsByUser->has($student->id),
                    "Student {$student->username} has a current course but no signal, so should not be listed",
                );

                continue;
            }

            $row = $rowsByUser[$student->id];
            $this->assertSame($expected['current_course']->id, $row['current_course']->id, "current course for {$student->username}");
            $this->assertSame($expected['signals'], $row['signals'], "signals for {$student->username}");
            $this->assertSame($expected['primary'], $row['primary'], "primary for {$student->username}");

            $expectedKeys = $expected['reasons']->sort()->values()->all();
            $rowKeys = collect(array_keys($row['reasons']))->sort()->values()->all();
            $this->assertSame($expectedKeys, $rowKeys, "reason keys for {$student->username}");
        }
    }

    public function test_the_page_renders_every_signal_with_its_evidence(): void
    {
        $page = $this->course('Page', 1, 2, 100);

        $failed = $this->student('mo_failed');
        $this->attempt($failed, $page->assessment, 'failed', 30, $this->daysAgo(3));
        $this->attempt($failed, $page->assessment, 'failed', 40, $this->daysAgo(1));

        $gone = $this->student('mo_gone');
        $this->activity($gone, 'login', 'Signed in', 0, $this->daysAgo(30));

        $this->actingAs($this->teacher())
            ->get(route('needs-attention'))
            ->assertOk()
            ->assertSee('MO_FAILED')
            ->assertSee('BOSS CHALLENGE FAILED')
            ->assertSee('REPEATED FAILURES')
            ->assertSee('Boss Challenge failed (score 40/100, attempt 2)')
            ->assertSee('Failed the Page challenge 2 times')
            ->assertSee('MO_GONE')
            ->assertSee('INACTIVE')
            ->assertSee('No activity in 30 days')
            ->assertSee('PRIMARY')
            ->assertSee('OTHER SIGNALS');
    }

    public function test_the_page_shows_the_empty_state_for_a_fresh_fleet(): void
    {
        $this->actingAs($this->teacher())
            ->get(route('needs-attention'))
            ->assertOk()
            ->assertSee('No signals');
    }

    /**
     * A cheap, deterministic "N full calendar days ago" timestamp six hours
     * past start-of-day. The boundary tests rely on the service snapping both
     * sides to start-of-day, so a +6h offset proves an event counts as N days
     * old rather than N-minus-a-few-hours.
     */
    private function daysAgo(int $days): Carbon
    {
        return now()->startOfDay()->subDays($days)->addHours(6);
    }

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function student(string $username, ?Carbon $createdAt = null): User
    {
        $user = User::factory()->create(['username' => $username]);

        if ($createdAt !== null) {
            $user->forceFill(['created_at' => $createdAt])->save();
        }

        return $user;
    }

    private function course(string $name, int $order, int $missionCount, int $passingScore): Course
    {
        $course = Course::factory()->create([
            'slug' => Str::slug($name).'-'.$order,
            'name' => $name,
            'order_num' => $order,
            'status' => 'active',
        ]);

        for ($i = 1; $i <= $missionCount; $i++) {
            Mission::factory()->create([
                'course_id' => $course->id,
                'order_num' => $i,
                'title' => "Mission {$name} {$i}",
            ]);
        }

        Assessment::factory()->create([
            'course_id' => $course->id,
            'title' => "{$name} Boss Challenge",
            'passing_score' => $passingScore,
            'status' => 'active',
        ]);

        return $course->load('assessment');
    }

    /**
     * @return array<int, Mission>
     */
    private function missions(Course $course): array
    {
        return $course->missions()->orderBy('order_num')->get()->all();
    }

    /**
     * @param  array<int, Mission>  $missions
     */
    private function completeAll(User $user, array $missions, ?Carbon $at = null): void
    {
        foreach ($missions as $mission) {
            $this->complete($user, $mission, $at);
        }
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

    private function attempt(User $user, ?Assessment $assessment, string $status, int $score, ?Carbon $at = null): void
    {
        $this->assertNotNull($assessment);

        AssessmentAttempt::factory()->create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => $status,
            'score' => $score,
            'passed_at' => $status === 'passed' ? ($at ?? now()) : null,
            'submitted_at' => $at ?? now(),
            'created_at' => $at,
        ]);
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

    /**
     * @return Collection<int, array{
     *     student: User,
     *     current_course: Course|null,
     *     signals: array<int, string>,
     *     primary: string,
     *     reasons: array<string, string>,
     *     last_activity_at: Carbon|null,
     * }>
     */
    private function rows(): Collection
    {
        return app(AttentionService::class)->list();
    }

    /**
     * @return array{
     *     student: User,
     *     current_course: Course|null,
     *     signals: array<int, string>,
     *     primary: string,
     *     reasons: array<string, string>,
     *     last_activity_at: Carbon|null,
     * }
     */
    private function rowFor(User $student): array
    {
        return $this->rows()->first(fn (array $row): bool => $row['student']->id === $student->id);
    }

    /**
     * Re-derive a student's attention signals from the exact predicates the
     * student-facing services use — DashboardService::currentCourse,
     * AssessmentService::attemptHistory/courseProgress, and the raw
     * progress/attempt/activity/xp rows for event recency — then assert the
     * SQL aggregation produced the same classification. Same no-duplicate-
     * formula defense as CourseAnalyticsTest.
     *
     * @return array{current_course: Course|null, signals: array<int, string>, primary: string|null, reasons: Collection<int, string>}
     */
    private function expectedFor(User $student): array
    {
        $dashboard = app(DashboardService::class);
        $assessments = app(AssessmentService::class);

        $course = $dashboard->currentCourse($student);

        if ($course === null) {
            return ['current_course' => null, 'signals' => [], 'primary' => null, 'reasons' => collect()];
        }

        $assessment = $assessments->forCourse($course);
        $courseProgress = $dashboard->courseProgress($student, $course);
        $done = $courseProgress['completed'];
        $total = $courseProgress['total'];
        $history = $assessments->attemptHistory($student, $course);
        $passing = (int) ($assessment?->passing_score ?? 0);

        $latest = $history->first();
        $failedCount = $history->where('status', 'failed')->count();
        $scored = $history->filter(
            fn (array $row): bool => in_array($row['status'], ['passed', 'failed'], true) && $row['score'] !== null,
        );
        $scoredCount = $scored->count();
        $totalScore = (int) $scored->sum(fn (array $row): int => (int) $row['score']);

        $lastCompletion = $student->progress()
            ->whereIn('mission_id', $course->missions()->select('id'))
            ->max('completed_at');

        $lastEvent = $this->lastEventFor($student);

        $enrolmentDays = (int) abs(now()->startOfDay()
            ->diffInDays(($student->created_at ?? now())->copy()->startOfDay()));

        $hasCoursesToStart = Course::query()
            ->where('status', 'active')
            ->whereHas('missions')
            ->exists();

        $fired = [];

        if ($assessment !== null && $latest !== null && $latest['status'] === 'failed') {
            $fired[] = 'boss_fail';
        }

        if ($failedCount >= AttentionService::REPEAT_FAIL_THRESHOLD) {
            $fired[] = 'repeat_fail';
        }

        if ($assessment !== null
            && $passing > 0
            && $scoredCount >= AttentionService::LOW_PERFORMANCE_MIN_ATTEMPTS
            && $totalScore * 100 < $passing * AttentionService::LOW_PERFORMANCE_PERCENT_OF_PASSING * $scoredCount
        ) {
            $fired[] = 'low_performance';
        }

        $stallDays = $lastCompletion !== null
            ? (int) abs(now()->startOfDay()->diffInDays(Carbon::parse($lastCompletion)->startOfDay()))
            : null;

        if ($done > 0
            && $done < $total
            && $stallDays !== null
            && $stallDays >= AttentionService::STALL_DAYS
        ) {
            $fired[] = 'stalled';
        }

        $inactivityDays = $lastEvent !== null
            ? (int) abs(now()->startOfDay()->diffInDays($lastEvent->copy()->startOfDay()))
            : null;

        if ($inactivityDays !== null && $inactivityDays >= AttentionService::INACTIVITY_DAYS) {
            $fired[] = 'inactive';
        }

        if ($hasCoursesToStart
            && ! $student->progress()->exists()
            && $enrolmentDays >= AttentionService::NEVER_STARTED_DAYS
        ) {
            $fired[] = 'never_started';
        }

        $signals = collect(AttentionService::SIGNAL_PRIORITY)
            ->filter(fn (string $signal): bool => in_array($signal, $fired, true))
            ->values();

        return [
            'current_course' => $course,
            'signals' => $signals->all(),
            'primary' => $signals->first(),
            'reasons' => $signals,
        ];
    }

    private function lastEventFor(User $student): ?Carbon
    {
        $sources = [
            $student->progress()->max('completed_at'),
            AssessmentAttempt::query()
                ->where('user_id', $student->id)
                ->selectRaw('MAX(COALESCE(passed_at, submitted_at, created_at)) as last')
                ->value('last'),
            $student->activities()->max('created_at'),
            XpTransaction::query()->where('user_id', $student->id)->max('created_at'),
        ];

        $last = null;

        foreach ($sources as $source) {
            if ($source === null) {
                continue;
            }

            $candidate = Carbon::parse($source);

            if ($last === null || $candidate->greaterThan($last)) {
                $last = $candidate;
            }
        }

        return $last;
    }

    /**
     * A small but varied fleet for the equivalence test. Alpha is the first
     * active course; Bravo the second; Zero has no missions.
     */
    private function buildFleet(): void
    {
        $alpha = $this->course('Alpha Equivalence', 1, 2, 100);
        $bravo = $this->course('Bravo Equivalence', 2, 1, 60);
        $this->course('Zero Equivalence', 3, 0, 100);

        $this->student('idle');

        $this->student('never_started', $this->daysAgo(40));

        $this->activity($this->student('inactive_only'), 'login', 'Signed in', 0, $this->daysAgo(25));

        $stalled = $this->student('stalled');
        $this->complete($stalled, $this->missions($alpha)[0], $this->daysAgo(20));

        $bossFail = $this->student('boss_fail');
        $this->completeAll($bossFail, $this->missions($alpha));
        $this->attempt($bossFail, $alpha->assessment, 'failed', 40, $this->daysAgo(3));

        $repeated = $this->student('repeated');
        $this->completeAll($repeated, $this->missions($alpha));
        $this->attempt($repeated, $alpha->assessment, 'failed', 40, $this->daysAgo(10));
        $this->attempt($repeated, $alpha->assessment, 'failed', 30, $this->daysAgo(2));

        $lowPerformance = $this->student('low_performance');
        $this->completeAll($lowPerformance, $this->missions($alpha));
        $this->attempt($lowPerformance, $alpha->assessment, 'failed', 50, $this->daysAgo(15));
        $this->attempt($lowPerformance, $alpha->assessment, 'failed', 55, $this->daysAgo(5));

        $cleared = $this->student('cleared');
        $this->completeAll($cleared, $this->missions($alpha));
        $this->attempt($cleared, $alpha->assessment, 'passed', 80, $this->daysAgo(10));
        $this->complete($cleared, $this->missions($bravo)[0], $this->daysAgo(2));
    }
}
