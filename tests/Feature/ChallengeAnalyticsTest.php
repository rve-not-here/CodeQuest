<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\ChallengeAnalyticsService;
use App\Services\DraftService;
use App\Services\XpService;
use App\Support\ReportFilters;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * US-1006 challenge analytics. Every expectation below is hand-computed
 * from the fixture rows: no metric is asserted merely to exist.
 */
class ChallengeAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private ChallengeAnalyticsService $analytics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
        $this->analytics = app(ChallengeAnalyticsService::class);
    }

    private function course(string $name = 'Course'): Course
    {
        return Course::factory()->create(['name' => $name, 'status' => 'active', 'order_num' => 1]);
    }

    private function mission(Course $course, string $title, string $difficulty = 'EASY'): Mission
    {
        $section = Section::factory()->create(['course_id' => $course->id]);

        return Mission::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'title' => $title,
            'difficulty' => $difficulty,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'points' => 50,
        ]);
    }

    private function submit(User $user, Mission $mission, string $code): void
    {
        $this->actingAs($user)
            ->post(route('mission.submit', $mission), ['code' => $code])
            ->assertRedirect();
    }

    private function filters(array $input = []): ReportFilters
    {
        return ReportFilters::fromArray($input, []);
    }

    public function test_empty_scope_yields_zero_counts_and_null_rates(): void
    {
        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(0, $summary['attempts']);
        $this->assertSame(0, $summary['completions']);
        $this->assertSame(0, $summary['failures']);
        $this->assertNull($summary['completion_rate']);
        $this->assertNull($summary['average_attempts']);
        $this->assertSame([], $summary['by_challenge']);
    }

    public function test_single_pass_records_one_completion(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course(), 'Solo');
        $this->submit($user, $mission, '<h1>Done</h1>');

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(1, $summary['completions']);
        $this->assertSame(0, $summary['failures']);
        $this->assertSame(100, $summary['completion_rate']);
        $this->assertSame(1, $summary['average_attempts']);
        $this->assertCount(1, $summary['by_challenge']);

        $row = $summary['by_challenge'][0];
        $this->assertSame($mission->id, $row['mission_id']);
        $this->assertSame('EASY', $row['difficulty']);
        $this->assertSame(1, $row['attempts']);
        $this->assertSame(0, $row['failure_rate']);
    }

    public function test_fail_then_pass_counts_both_submissions_once_completed(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course(), 'Retry', 'MEDIUM');
        $this->submit($user, $mission, 'wrong');
        $this->submit($user, $mission, 'still wrong');
        $this->submit($user, $mission, '<h1>Done</h1>');

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(3, $summary['attempts']);
        $this->assertSame(1, $summary['completions']);
        $this->assertSame(2, $summary['failures']);
        $this->assertSame(100, $summary['completion_rate']);
        $this->assertSame(3, $summary['average_attempts']);
        $this->assertSame(67, $summary['by_challenge'][0]['failure_rate']);
    }

    public function test_completed_pair_without_completion_stays_incomplete(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course(), 'Stuck');
        $this->submit($user, $mission, 'wrong');

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(0, $summary['completions']);
        $this->assertSame(0, $summary['completion_rate']);
        $this->assertSame(1, $summary['average_attempts']);
    }

    public function test_multiple_students_share_one_mission(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $mission = $this->mission($this->course(), 'Shared');
        $this->submit($first, $mission, '<h1>Done</h1>');
        $this->submit($second, $mission, 'wrong');

        $summary = $this->analytics->summarize(null, null, $this->filters());

        // Pair grain: two attempted pairs, one completed → 50, not 100.
        $this->assertSame(2, $summary['attempts']);
        $this->assertSame(1, $summary['completions']);
        $this->assertSame(1, $summary['failures']);
        $this->assertSame(50, $summary['completion_rate']);
        $this->assertSame(1, $summary['average_attempts']);
    }

    public function test_per_challenge_rows_reconcile_with_totals(): void
    {
        $user = User::factory()->create();
        $courseA = $this->course('Alpha');
        $courseB = $this->course('Beta');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One', 'HARD');
        $this->submit($user, $missionA, '<h1>Done</h1>');
        $this->submit($user, $missionB, 'wrong');
        $this->submit($user, $missionB, '<h1>Done</h1>');

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(3, $summary['attempts']);
        $this->assertSame(2, $summary['completions']);
        $this->assertSame(1, $summary['failures']);
        $this->assertSame(100, $summary['completion_rate']);
        $this->assertSame(2, $summary['average_attempts']);
        $this->assertCount(2, $summary['by_challenge']);

        $rowA = collect($summary['by_challenge'])->firstWhere('mission_id', $missionA->id);
        $rowB = collect($summary['by_challenge'])->firstWhere('mission_id', $missionB->id);
        $this->assertSame($courseA->id, $rowA['course_id']);
        $this->assertSame('HARD', $rowB['difficulty']);
        $this->assertSame(
            $summary['attempts'],
            collect($summary['by_challenge'])->sum('attempts')
        );
        $this->assertSame(
            $summary['completions'],
            collect($summary['by_challenge'])->sum('completions')
        );
        $this->assertSame(
            $summary['failures'],
            collect($summary['by_challenge'])->sum('failures')
        );
    }

    public function test_drafts_and_hint_purchases_are_not_attempts(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $mission = $this->mission($course, 'Drafted');
        $earner = $this->mission($course, 'Earner');

        app(DraftService::class)->save($user, $mission, '<h1>unfinished');
        $this->submit($user, $earner, '<h1>Done</h1>');
        $this->assertTrue(app(XpService::class)->spendHint($user, $mission, 1));

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(1, $summary['completions']);
        $this->assertSame(0, $summary['failures']);
    }

    public function test_resubmit_after_completion_creates_nothing(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course(), 'Done Twice');
        $this->submit($user, $mission, '<h1>Done</h1>');
        $this->submit($user, $mission, '<h1>Done again</h1>');

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(1, $summary['completions']);
    }

    public function test_split_window_keeps_rate_coherent(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course(), 'Split');
        $this->submit($user, $mission, 'wrong');
        $this->submit($user, $mission, '<h1>Done</h1>');

        DB::table('the404_xp_transactions')->where('mission_id', $mission->id)->update(['created_at' => '2026-04-10 09:00:00']);
        DB::table('the404_progress')->where('mission_id', $mission->id)->update(['completed_at' => '2026-05-10 09:00:00']);

        $summary = $this->analytics->summarize(
            null,
            null,
            $this->filters(['from' => '2026-05-01', 'to' => '2026-05-31'])
        );

        // Only the in-range completion counts, and the pair is genuinely
        // complete in May: numerator and denominator stay coherent, the
        // rate can never exceed 100.
        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(1, $summary['completions']);
        $this->assertSame(0, $summary['failures']);
        $this->assertSame(100, $summary['completion_rate']);
        $this->assertSame(1, $summary['average_attempts']);
        $this->assertLessThanOrEqual(100, $summary['completion_rate']);
    }

    public function test_repeated_failures_accumulate_without_completion(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course(), 'Hopeless');
        $this->submit($user, $mission, 'wrong one');
        $this->submit($user, $mission, 'wrong two');
        $this->submit($user, $mission, 'wrong three');

        $summary = $this->analytics->summarize(null, null, $this->filters());

        // Every persisted submission counts: three fails, no completion.
        $this->assertSame(3, $summary['attempts']);
        $this->assertSame(0, $summary['completions']);
        $this->assertSame(3, $summary['failures']);
        $this->assertSame(0, $summary['completion_rate']);
        $this->assertSame(3, $summary['average_attempts']);
    }

    public function test_date_range_filters_both_evidence_tables(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $april = $this->mission($course, 'April');
        $may = $this->mission($course, 'May');
        $this->submit($user, $april, '<h1>Done</h1>');
        $this->submit($user, $may, 'wrong');

        DB::table('the404_progress')->where('mission_id', $april->id)->update(['completed_at' => '2026-04-10 09:00:00']);
        DB::table('the404_xp_transactions')->where('mission_id', $may->id)->update(['created_at' => '2026-05-10 09:00:00']);

        $summary = $this->analytics->summarize(
            null,
            null,
            $this->filters(['from' => '2026-05-01', 'to' => '2026-05-31'])
        );

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(0, $summary['completions']);
        $this->assertSame(1, $summary['failures']);
        $this->assertSame(0, $summary['completion_rate']);
    }

    public function test_same_day_range_includes_that_day_only(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $inside = $this->mission($course, 'Inside');
        $outside = $this->mission($course, 'Outside');
        $this->submit($user, $inside, '<h1>Done</h1>');
        $this->submit($user, $outside, '<h1>Done</h1>');

        DB::table('the404_progress')->where('mission_id', $inside->id)->update(['completed_at' => '2026-05-10 00:00:00']);
        DB::table('the404_progress')->where('mission_id', $outside->id)->update(['completed_at' => '2026-05-11 00:00:00']);

        $summary = $this->analytics->summarize(
            null,
            null,
            $this->filters(['from' => '2026-05-10', 'to' => '2026-05-10'])
        );

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(1, $summary['completions']);
    }

    public function test_course_and_student_filters_scope_metrics(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $courseA = $this->course('Alpha');
        $courseB = $this->course('Beta');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');
        $this->submit($first, $missionA, '<h1>Done</h1>');
        $this->submit($second, $missionB, '<h1>Done</h1>');

        $byCourse = $this->analytics->summarize(null, collect([$courseA->id]), $this->filters());
        $this->assertSame(1, $byCourse['attempts']);
        $this->assertCount(1, $byCourse['by_challenge']);

        $byStudent = $this->analytics->summarize(
            collect([$first->id]),
            null,
            $this->filters(['student_id' => $first->id])
        );
        $this->assertSame(1, $byStudent['attempts']);
        $this->assertSame(100, $byStudent['completion_rate']);
    }

    public function test_status_filter_is_rejected_without_vocabulary(): void
    {
        try {
            $this->filters(['status' => 'passed']);
            $this->fail('Status with no vocabulary must throw.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
    }

    public function test_teacher_scope_excludes_other_courses_and_students(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $courseA = $this->course('Alpha');
        $courseB = $this->course('Beta');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');
        $this->submit($student, $missionA, '<h1>Done</h1>');
        $this->submit($other, $missionB, '<h1>Done</h1>');

        $summary = $this->analytics->summarize(collect([$student->id]), collect([$courseA->id]), $this->filters());

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(1, $summary['completions']);
        $this->assertCount(1, $summary['by_challenge']);
    }

    public function test_tampered_scope_yields_empty_metrics(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course(), 'Real');
        $this->submit($user, $mission, '<h1>Done</h1>');

        $summary = $this->analytics->summarize(collect([$user->id]), collect([999999]), $this->filters());

        $this->assertSame(0, $summary['attempts']);
        $this->assertNull($summary['completion_rate']);
        $this->assertNull($summary['average_attempts']);
        $this->assertSame([], $summary['by_challenge']);
    }

    public function test_admin_fleet_scope_sees_everything(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $courseA = $this->course('Alpha');
        $courseB = $this->course('Beta');
        $this->submit($first, $this->mission($courseA, 'Alpha One'), '<h1>Done</h1>');
        $this->submit($second, $this->mission($courseB, 'Beta One'), 'wrong');

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(2, $summary['attempts']);
        $this->assertSame(1, $summary['completions']);
        $this->assertSame(1, $summary['failures']);
        $this->assertSame(50, $summary['completion_rate']);
        $this->assertSame(1, $summary['average_attempts']);
        $this->assertCount(2, $summary['by_challenge']);
    }

    public function test_mission_edits_do_not_rewrite_history(): void
    {
        $user = User::factory()->create();
        $mission = $this->mission($this->course(), 'Edited', 'EASY');
        $this->submit($user, $mission, '<h1>Done</h1>');

        $mission->description = 'Rewritten instructions.';
        $mission->points = 500;
        $mission->difficulty = 'HARD';
        $mission->save();

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(1, $summary['completions']);
        $this->assertSame(100, $summary['completion_rate']);
        $this->assertSame('HARD', $summary['by_challenge'][0]['difficulty']);
    }

    public function test_query_count_stays_bounded_as_history_grows(): void
    {
        $course = $this->course();
        $mission = $this->mission($course, 'Busy');

        foreach (range(1, 3) as $i) {
            $user = User::factory()->create();
            $this->submit($user, $mission, 'wrong');
            $this->submit($user, $mission, '<h1>Done</h1>');
        }

        $queries = $this->countQueries(fn () => $this->analytics->summarize(null, null, $this->filters()));

        $this->assertLessThanOrEqual(12, $queries);

        $second = $this->mission($this->course('Second'), 'Busier');

        foreach (range(1, 6) as $i) {
            $user = User::factory()->create();
            $this->submit($user, $second, 'wrong');
            $this->submit($user, $second, '<h1>Done</h1>');
        }

        $grown = $this->countQueries(fn () => $this->analytics->summarize(null, null, $this->filters()));

        $this->assertLessThanOrEqual($queries + 3, $grown);
    }

    private function countQueries(callable $fn): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $fn();
        $count = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        return $count;
    }
}
