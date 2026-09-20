<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\User;
use App\Services\AssessmentAnalyticsService;
use App\Support\ReportFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * US-1005 assessment analytics. Every expectation below is hand-computed
 * from the fixture rows: no metric may be asserted merely to exist.
 */
class AssessmentAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private AssessmentAnalyticsService $analytics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analytics = app(AssessmentAnalyticsService::class);
    }

    private function course(string $name = 'Course'): Course
    {
        return Course::factory()->create(['name' => $name, 'status' => 'active']);
    }

    private function assessment(Course $course, int $passingScore = 70): Assessment
    {
        return Assessment::factory()->create([
            'course_id' => $course->id,
            'passing_score' => $passingScore,
            'status' => 'active',
        ]);
    }

    private function attempt(
        Assessment $assessment,
        User $user,
        string $status,
        ?int $score = null,
        string $createdAt = '2026-05-15 12:00:00',
    ): AssessmentAttempt {
        return AssessmentAttempt::factory()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'status' => $status,
            'score' => $score,
            'created_at' => $createdAt,
        ]);
    }

    private function filters(array $input = []): ReportFilters
    {
        return ReportFilters::fromArray($input, []);
    }

    public function test_zero_attempts_yields_zero_counts_and_null_rates(): void
    {
        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(0, $summary['attempts']);
        $this->assertSame(0, $summary['passes']);
        $this->assertSame(0, $summary['failures']);
        $this->assertNull($summary['average_score']);
        $this->assertNull($summary['pass_rate']);
        $this->assertSame(0, $summary['retries']);
        $this->assertSame(0, $summary['completion']);
        $this->assertSame([], $summary['by_assessment']);
    }

    public function test_one_passing_attempt(): void
    {
        $user = User::factory()->create();
        $assessment = $this->assessment($this->course());
        $this->attempt($assessment, $user, 'passed', 80);

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(1, $summary['passes']);
        $this->assertSame(0, $summary['failures']);
        $this->assertSame(80, $summary['average_score']);
        $this->assertSame(100, $summary['pass_rate']);
        $this->assertSame(0, $summary['retries']);
        $this->assertSame(1, $summary['completion']);
    }

    public function test_one_failing_attempt(): void
    {
        $user = User::factory()->create();
        $assessment = $this->assessment($this->course());
        $this->attempt($assessment, $user, 'failed', 40);

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(0, $summary['passes']);
        $this->assertSame(1, $summary['failures']);
        $this->assertSame(40, $summary['average_score']);
        $this->assertSame(0, $summary['pass_rate']);
        $this->assertSame(0, $summary['retries']);
        $this->assertSame(0, $summary['completion']);
    }

    public function test_in_flight_attempt_counts_without_score_or_verdict(): void
    {
        $user = User::factory()->create();
        $assessment = $this->assessment($this->course());
        $this->attempt($assessment, $user, 'started');
        $this->attempt($assessment, $user, 'submitted');

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(2, $summary['attempts']);
        $this->assertSame(0, $summary['passes']);
        $this->assertSame(0, $summary['failures']);
        $this->assertNull($summary['average_score']);
        $this->assertNull($summary['pass_rate']);
        $this->assertSame(1, $summary['retries']);
        $this->assertSame(0, $summary['completion']);
    }

    public function test_retry_sequence_counts_once_per_extra_row(): void
    {
        $user = User::factory()->create();
        $assessment = $this->assessment($this->course());
        $this->attempt($assessment, $user, 'failed', 30);
        $this->attempt($assessment, $user, 'failed', 55);
        $this->attempt($assessment, $user, 'passed', 90);

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(3, $summary['attempts']);
        $this->assertSame(1, $summary['passes']);
        $this->assertSame(2, $summary['failures']);
        $this->assertSame(58, $summary['average_score']);
        $this->assertSame(100, $summary['pass_rate']);
        $this->assertSame(2, $summary['retries']);
        $this->assertSame(1, $summary['completion']);
    }

    public function test_multiple_students_share_denominators(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $assessment = $this->assessment($this->course());
        $this->attempt($assessment, $first, 'passed', 80);
        $this->attempt($assessment, $second, 'failed', 30);

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(2, $summary['attempts']);
        $this->assertSame(55, $summary['average_score']);
        $this->assertSame(50, $summary['pass_rate']);
        $this->assertSame(0, $summary['retries']);
        $this->assertSame(1, $summary['completion']);
    }

    public function test_per_assessment_breakdown_matches_totals(): void
    {
        $user = User::factory()->create();
        $courseA = $this->course('Alpha');
        $courseB = $this->course('Beta');
        $assessmentA = $this->assessment($courseA);
        $assessmentB = $this->assessment($courseB);
        $this->attempt($assessmentA, $user, 'passed', 80);
        $this->attempt($assessmentB, $user, 'failed', 40);
        $this->attempt($assessmentB, $user, 'passed', 90);

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(3, $summary['attempts']);
        $this->assertSame(2, $summary['completion']);
        $this->assertSame(100, $summary['pass_rate']);
        $this->assertCount(2, $summary['by_assessment']);

        $rowA = collect($summary['by_assessment'])->firstWhere('assessment_id', $assessmentA->id);
        $this->assertSame($courseA->id, $rowA['course_id']);
        $this->assertSame(1, $rowA['attempts']);
        $this->assertSame(1, $rowA['passes']);
        $this->assertSame(80, $rowA['average_score']);
        $this->assertSame(0, $rowA['retries']);
        $this->assertSame(1, $rowA['completion']);

        $rowB = collect($summary['by_assessment'])->firstWhere('assessment_id', $assessmentB->id);
        $this->assertSame(2, $rowB['attempts']);
        $this->assertSame(1, $rowB['retries']);
        $this->assertSame(65, $rowB['average_score']);
        $this->assertSame(1, $rowB['completion']);
    }

    public function test_date_range_filters_attempts(): void
    {
        $user = User::factory()->create();
        $assessment = $this->assessment($this->course());
        $this->attempt($assessment, $user, 'passed', 80, '2026-04-10 09:00:00');
        $this->attempt($assessment, $user, 'failed', 40, '2026-05-10 09:00:00');
        $this->attempt($assessment, $user, 'passed', 90, '2026-06-10 09:00:00');

        $summary = $this->analytics->summarize(
            null,
            null,
            $this->filters(['from' => '2026-05-01', 'to' => '2026-05-31'])
        );

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(0, $summary['passes']);
        $this->assertSame(1, $summary['failures']);
        $this->assertSame(40, $summary['average_score']);
    }

    public function test_same_day_range_includes_that_day_only(): void
    {
        $user = User::factory()->create();
        $assessment = $this->assessment($this->course());
        $this->attempt($assessment, $user, 'passed', 80, '2026-05-10 00:00:00');
        $this->attempt($assessment, $user, 'passed', 90, '2026-05-11 00:00:00');

        $summary = $this->analytics->summarize(
            null,
            null,
            $this->filters(['from' => '2026-05-10', 'to' => '2026-05-10'])
        );

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(80, $summary['average_score']);
    }

    public function test_course_filter_scopes_metrics(): void
    {
        $user = User::factory()->create();
        $courseA = $this->course('Alpha');
        $courseB = $this->course('Beta');
        $this->attempt($this->assessment($courseA), $user, 'passed', 80);
        $this->attempt($this->assessment($courseB), $user, 'failed', 40);

        $summary = $this->analytics->summarize(null, collect([$courseA->id]), $this->filters());

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(1, $summary['passes']);
        $this->assertSame(1, $summary['completion']);
        $this->assertCount(1, $summary['by_assessment']);
    }

    public function test_student_filter_scopes_metrics(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $assessment = $this->assessment($this->course());
        $this->attempt($assessment, $first, 'passed', 80);
        $this->attempt($assessment, $second, 'failed', 30);

        $summary = $this->analytics->summarize(
            collect([$first->id]),
            null,
            $this->filters(['student_id' => $first->id])
        );

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(100, $summary['pass_rate']);
    }

    public function test_teacher_scope_excludes_unauthorized_course(): void
    {
        $student = User::factory()->create();
        $courseA = $this->course('Alpha');
        $courseB = $this->course('Beta');
        $this->attempt($this->assessment($courseA), $student, 'passed', 80);
        $this->attempt($this->assessment($courseB), $student, 'passed', 90);

        $summary = $this->analytics->summarize(collect([$student->id]), collect([$courseA->id]), $this->filters());

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(80, $summary['average_score']);
        $this->assertSame(1, $summary['completion']);
    }

    public function test_teacher_scope_excludes_unauthorized_student(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $assessment = $this->assessment($this->course());
        $this->attempt($assessment, $first, 'passed', 80);
        $this->attempt($assessment, $second, 'passed', 90);

        $summary = $this->analytics->summarize(collect([$first->id]), null, $this->filters());

        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(80, $summary['average_score']);
    }

    public function test_admin_fleet_scope_sees_everything(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $courseA = $this->course('Alpha');
        $courseB = $this->course('Beta');
        $this->attempt($this->assessment($courseA), $first, 'passed', 80);
        $this->attempt($this->assessment($courseB), $second, 'failed', 40);

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(2, $summary['attempts']);
        $this->assertSame(60, $summary['average_score']);
        $this->assertSame(50, $summary['pass_rate']);
        $this->assertSame(1, $summary['completion']);
        $this->assertCount(2, $summary['by_assessment']);
    }

    public function test_historical_passing_score_change_preserves_results(): void
    {
        $user = User::factory()->create();
        $assessment = $this->assessment($this->course(), 70);
        $this->attempt($assessment, $user, 'passed', 80);

        $assessment->passing_score = 95;
        $assessment->save();

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(1, $summary['passes']);
        $this->assertSame(100, $summary['pass_rate']);
        $this->assertSame(80, $summary['average_score']);
    }

    public function test_historical_grading_rule_change_preserves_results(): void
    {
        $user = User::factory()->create();
        $assessment = $this->assessment($this->course());
        $this->attempt($assessment, $user, 'passed', 80);

        $assessment->grading_rule = json_encode([['type' => 'contains', 'value' => 'nothing-matches-this']]);
        $assessment->save();

        $summary = $this->analytics->summarize(null, null, $this->filters());

        $this->assertSame(1, $summary['passes']);
        $this->assertSame(80, $summary['average_score']);
    }

    public function test_filter_outside_authorized_scope_yields_empty_metrics(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $this->attempt($this->assessment($course), $user, 'passed', 80);

        $summary = $this->analytics->summarize(
            collect([$user->id]),
            collect([999999]),
            $this->filters()
        );

        $this->assertSame(0, $summary['attempts']);
        $this->assertNull($summary['average_score']);
        $this->assertNull($summary['pass_rate']);
        $this->assertSame(0, $summary['completion']);
        $this->assertSame([], $summary['by_assessment']);
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

    public function test_pass_rate_uses_pair_grain_across_assessments(): void
    {
        $user = User::factory()->create();
        $courseA = $this->course('Alpha');
        $courseB = $this->course('Beta');
        $this->attempt($this->assessment($courseA), $user, 'passed', 80);
        $this->attempt($this->assessment($courseB), $user, 'failed', 40);

        $summary = $this->analytics->summarize(null, null, $this->filters());

        // One pair passed out of two terminal pairs: 50, not 100. A bare
        // distinct-student ratio would collapse both outcomes into one user.
        $this->assertSame(2, $summary['attempts']);
        $this->assertSame(50, $summary['pass_rate']);
        $this->assertSame(1, $summary['completion']);
    }

    public function test_retry_classification_uses_full_history_across_date_boundary(): void
    {
        $user = User::factory()->create();
        $assessment = $this->assessment($this->course());
        $this->attempt($assessment, $user, 'failed', 30, '2026-04-10 09:00:00');
        $this->attempt($assessment, $user, 'passed', 90, '2026-05-10 09:00:00');

        $summary = $this->analytics->summarize(
            null,
            null,
            $this->filters(['from' => '2026-05-01', 'to' => '2026-05-31'])
        );

        // Only the in-range retry counts, but it still counts as a retry:
        // history proves a prior row for the pair outside the window.
        $this->assertSame(1, $summary['attempts']);
        $this->assertSame(1, $summary['retries']);
        $this->assertSame(1, $summary['passes']);
        $this->assertSame(100, $summary['pass_rate']);
        $this->assertSame(1, $summary['completion']);
    }

    public function test_status_filter_narrows_to_persisted_attempt_states(): void
    {
        $user = User::factory()->create();
        $assessment = $this->assessment($this->course());
        $this->attempt($assessment, $user, 'passed', 80);
        $this->attempt($assessment, $user, 'failed', 40);

        $passed = $this->analytics->summarize(
            null,
            null,
            ReportFilters::fromArray(
                ['status' => 'passed'],
                AssessmentAnalyticsService::STATUSES
            )
        );

        $this->assertSame(1, $passed['attempts']);
        $this->assertSame(1, $passed['passes']);
        $this->assertSame(0, $passed['failures']);
        $this->assertSame(80, $passed['average_score']);

        $failed = $this->analytics->summarize(
            null,
            null,
            ReportFilters::fromArray(
                ['status' => 'failed'],
                AssessmentAnalyticsService::STATUSES
            )
        );

        $this->assertSame(1, $failed['attempts']);
        $this->assertSame(40, $failed['average_score']);
    }

    public function test_status_filter_rejects_invented_status(): void
    {
        $this->expectException(ValidationException::class);

        ReportFilters::fromArray(
            ['status' => 'successful'],
            AssessmentAnalyticsService::STATUSES
        );
    }

    public function test_status_filter_combines_with_authorized_scope(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $course = $this->course();
        $assessment = $this->assessment($course);
        $this->attempt($assessment, $first, 'passed', 80);
        $this->attempt($assessment, $second, 'failed', 40);

        $summary = $this->analytics->summarize(
            collect([$first->id]),
            collect([$course->id]),
            ReportFilters::fromArray(
                ['status' => 'failed'],
                AssessmentAnalyticsService::STATUSES
            )
        );

        // The failed row belongs to the unauthorized student, so the
        // authorized scope plus status filter yields nothing at all.
        $this->assertSame(0, $summary['attempts']);
        $this->assertNull($summary['average_score']);
        $this->assertNull($summary['pass_rate']);
        $this->assertSame(0, $summary['completion']);
    }

    public function test_query_count_stays_bounded_as_history_grows(): void
    {
        $course = $this->course();
        $assessment = $this->assessment($course);

        foreach (range(1, 3) as $i) {
            $user = User::factory()->create();
            $this->attempt($assessment, $user, 'passed', 80);
            $this->attempt($assessment, $user, 'failed', 40);
        }

        $queries = $this->countQueries(fn () => $this->analytics->summarize(null, null, $this->filters()));

        $this->assertLessThanOrEqual(12, $queries);

        $secondCourse = $this->course('Second');
        $secondAssessment = $this->assessment($secondCourse);

        foreach (range(1, 6) as $i) {
            $user = User::factory()->create();
            $this->attempt($secondAssessment, $user, 'passed', 80);
            $this->attempt($secondAssessment, $user, 'failed', 40);
        }

        $grown = $this->countQueries(fn () => $this->analytics->summarize(null, null, $this->filters()));

        $this->assertLessThanOrEqual($queries + 3, $grown);
    }
}
