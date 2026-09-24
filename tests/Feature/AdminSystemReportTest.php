<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Services\AdminSystemReportService;
use App\Services\AssessmentAnalyticsService;
use App\Services\AssessmentService;
use App\Services\ChallengeAnalyticsService;
use App\Services\CourseAnalyticsService;
use App\Services\MissionService;
use App\Services\TeacherDashboardService;
use App\Services\XpService;
use App\Support\ReportFilters;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-1008 admin system report. Hand-computed fixtures for every scalar the
 * report owns, and direct equality against the authoritative services for
 * every figure it composes: challenges, assessments, gamification, course
 * rows, and active students.
 */
class AdminSystemReportTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    private static int $orderSequence = 5000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    private function course(string $slug, string $name = 'Course'): Course
    {
        self::$orderSequence++;

        $course = new Course([
            'slug' => $slug,
            'name' => $name.' '.self::$orderSequence,
            'type' => 'html',
            'status' => 'active',
            'order_num' => self::$orderSequence,
        ]);
        $course->save();

        return $course;
    }

    private function mission(Course $course, string $title): Mission
    {
        self::$orderSequence++;

        $section = new Section([
            'course_id' => $course->id,
            'order_num' => self::$orderSequence,
            'title' => 'Section '.self::$orderSequence,
        ]);
        $section->save();

        $mission = new Mission([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'order_num' => self::$orderSequence,
            'title' => $title,
            'difficulty' => 'EASY',
            'points' => 50,
            'validate_rule' => json_encode([['type' => 'contains', 'value' => '<h1>']]),
            'version' => 1,
        ]);
        $mission->save();

        return $mission->fresh();
    }

    private function assessment(Course $course): Assessment
    {
        return Assessment::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'passing_score' => 70,
            'grading_rule' => json_encode([['type' => 'contains', 'value' => 'SIGNAL']]),
        ]);
    }

    private function complete(User $user, Mission $mission): void
    {
        $result = app(MissionService::class)->submit($user, $mission, '<h1>Title</h1>');
        $this->assertTrue($result['passed']);
    }

    private function wrongSubmission(User $user, Mission $mission, ?string $at = null): void
    {
        DB::table('the404_xp_transactions')->insert([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => -10,
            'type' => XpService::TYPE_WRONG_SUBMISSION,
            'description' => 'Wrong submission on mission: '.$mission->title,
            'created_at' => $at ?? now()->toDateTimeString(),
        ]);
    }

    private function attemptAssessment(User $user, Course $course, string $code): void
    {
        $service = app(AssessmentService::class);
        $attempt = $service->beginAttempt($user, $course);
        $service->submitAttempt($user, $attempt, $code);
        $service->evaluateAttempt($user, $attempt);
    }

    private function filters(array $input = []): ReportFilters
    {
        return ReportFilters::fromArray($input, []);
    }

    private function report(?array $courseIds = null, array $input = []): array
    {
        return app(AdminSystemReportService::class)->forSystem(
            $courseIds === null ? null : collect($courseIds),
            $this->filters($input),
        );
    }

    public function test_empty_system_reports_zeros_and_nulls(): void
    {
        $report = $this->report();

        $this->assertSame(0, $report['users']['total']);
        $this->assertSame(0, $report['users']['active']);
        $this->assertSame(0, $report['users']['inactive']);
        $this->assertSame(['student' => 0, 'teacher' => 0, 'admin' => 0, 'operator' => 0], $report['users']['by_role']);

        $this->assertSame(['courses' => 0, 'sections' => 0, 'challenges' => 0, 'boss_challenges' => 0], $report['catalog']);

        $this->assertSame(0, $report['learning']['course_completions']);
        $this->assertSame(0, $report['learning']['completed_challenges']);
        $this->assertSame(0, $report['learning']['active_students']);

        $this->assertSame(0, $report['challenges']['completions']);
        $this->assertSame(0, $report['challenges']['attempts']);
        $this->assertNull($report['challenges']['completion_rate']);
        $this->assertNull($report['challenges']['average_attempts']);

        $this->assertSame(0, $report['assessments']['attempts']);
        $this->assertNull($report['assessments']['pass_rate']);

        $this->assertSame(0, $report['gamification']['awarded']);
        $this->assertSame(0, $report['gamification']['outstanding']);

        $this->assertSame(0, $report['period']['completions']);
        $this->assertSame(0, $report['period']['wrong_submissions']);
        $this->assertSame(0, $report['period']['assessment_attempts']);
        $this->assertSame(0, $report['period']['assessment_passes']);
        $this->assertNull($report['period']['from']);
        $this->assertNull($report['period']['to_exclusive']);
    }

    public function test_full_system_matches_authoritative_services(): void
    {
        $studentA = User::factory()->create(['role' => 'student']);
        $studentB = User::factory()->create(['role' => 'student']);
        $quiet = User::factory()->create(['role' => 'student']);
        $quiet->forceFill(['status' => 'inactive'])->save();
        $teacher = User::factory()->teacher()->create();
        User::factory()->admin()->create();
        User::factory()->create(['role' => 'operator']);

        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $courseB->update(['order_num' => $courseA->order_num]);
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');
        $this->assessment($courseA);
        $this->assessment($courseB);

        // A classroom exists but must not distort fleet facts.
        $this->classroomFor($teacher, [$studentA, $studentB], [$courseA, $courseB]);

        $this->complete($studentA, $missionA);
        $this->complete($studentB, $missionB);
        $this->wrongSubmission($studentA, $missionA);
        $this->attemptAssessment($studentA, $courseA, 'SIGNAL answer');
        $this->attemptAssessment($studentB, $courseB, 'NOPE');

        $report = $this->report();

        // Users: hand-computed fleet accounts, staff never learners.
        $this->assertSame(6, $report['users']['total']);
        $this->assertSame(5, $report['users']['active']);
        $this->assertSame(1, $report['users']['inactive']);
        $this->assertSame(['student' => 3, 'teacher' => 1, 'admin' => 1, 'operator' => 1], $report['users']['by_role']);

        // Catalog: hand-computed content facts.
        $this->assertSame(['courses' => 2, 'sections' => 2, 'challenges' => 2, 'boss_challenges' => 2], $report['catalog']);

        // Learning: hand-computed counts plus the owned-service reconciliation.
        $this->assertSame(2, $report['learning']['completed_challenges']);
        $this->assertSame(2, $report['learning']['active_students']);
        $expectedRows = app(CourseAnalyticsService::class)->overview();
        $this->assertSame(
            (int) $expectedRows->sum(fn (array $row): int => $row['buckets']['completed']),
            $report['learning']['course_completions']
        );

        // Composed subsections equal the authoritative outputs verbatim.
        $this->assertSame(app(ChallengeAnalyticsService::class)->summarize(), $report['challenges']);
        $this->assertSame(app(AssessmentAnalyticsService::class)->summarize(), $report['assessments']);

        // Gamification is the fleet summary verbatim plus an explicit fleet
        // scope marker: XpService owns no student-scoped summary.
        $this->assertSame('fleet', $report['gamification']['scope']);
        $gamification = $report['gamification'];
        unset($gamification['scope']);
        $this->assertSame(app(XpService::class)->fleetSummary(), $gamification);

        // Gamification moved: completions and the assessment pass awarded XP.
        // Only the two acting students hold ledger rows; the failed attempt
        // awards nothing and the quiet/staff accounts hold nothing.
        $this->assertGreaterThan(0, $report['gamification']['awarded']);
        $this->assertSame(2, $report['gamification']['accounts']);

        // Period: 2 completions, 1 wrong submission, 2 attempts, 1 pass.
        $this->assertSame(2, $report['period']['completions']);
        $this->assertSame(1, $report['period']['wrong_submissions']);
        $this->assertSame(2, $report['period']['assessment_attempts']);
        $this->assertSame(1, $report['period']['assessment_passes']);
    }

    public function test_staff_without_learning_events_are_not_active_students(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        User::factory()->teacher()->create();
        User::factory()->admin()->create();

        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($student, $mission);

        $report = $this->report();

        $this->assertSame(1, $report['learning']['active_students']);
        $this->assertSame($report['learning']['active_students'], app(TeacherDashboardService::class)->countActiveStudents());
        $this->assertSame(['student' => 1, 'teacher' => 1, 'admin' => 1, 'operator' => 0], $report['users']['by_role']);
    }

    public function test_date_range_narrows_period_and_composed_sections_only(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);

        DB::table('the404_progress')->where('mission_id', $missionA->id)->update(['completed_at' => '2026-05-10 09:00:00']);
        DB::table('the404_progress')->where('mission_id', $missionB->id)->update(['completed_at' => '2026-04-10 09:00:00']);

        $report = $this->report(null, ['from' => '2026-05-01', 'to' => '2026-05-31']);
        $filters = $this->filters(['from' => '2026-05-01', 'to' => '2026-05-31']);

        // Current-state sections do not move with the date range.
        $this->assertSame(2, $report['learning']['completed_challenges']);
        $this->assertSame(2, $report['catalog']['challenges']);

        // The period narrows to May: only the A completion.
        $this->assertSame(1, $report['period']['completions']);
        $this->assertSame('2026-05-01 00:00:00', $report['period']['from']);
        $this->assertSame('2026-06-01 00:00:00', $report['period']['to_exclusive']);

        // Composed sections follow their owning services under the same range.
        $this->assertSame(app(ChallengeAnalyticsService::class)->summarize(null, null, $filters), $report['challenges']);
        $this->assertSame(app(AssessmentAnalyticsService::class)->summarize(null, null, $filters), $report['assessments']);
    }

    public function test_course_filter_narrows_scoped_sections(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);
        $this->wrongSubmission($student, $missionB);

        $report = $this->report(null, ['course_id' => $courseB->id]);
        $filters = $this->filters(['course_id' => $courseB->id]);

        // Catalog narrows to the requested course.
        $this->assertSame(['courses' => 1, 'sections' => 1, 'challenges' => 1, 'boss_challenges' => 0], $report['catalog']);

        // Learning narrows: one completion, one active student.
        $this->assertSame(1, $report['learning']['completed_challenges']);
        $this->assertSame(1, $report['learning']['active_students']);

        // Composed sections follow their owning services under the same scope.
        $this->assertSame(app(ChallengeAnalyticsService::class)->summarize(null, collect([$courseB->id]), $filters), $report['challenges']);

        // Period narrows: only B's completion and wrong submission.
        $this->assertSame(1, $report['period']['completions']);
        $this->assertSame(1, $report['period']['wrong_submissions']);

        // Fleet facts stay fleet by contract.
        $this->assertSame(1, $report['users']['total']);
        $this->assertGreaterThan(0, $report['gamification']['awarded']);
    }

    public function test_student_filter_drills_down_to_one_learner(): void
    {
        $studentA = User::factory()->create(['role' => 'student']);
        $studentB = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($studentA, $mission);
        $this->complete($studentB, $mission);
        $this->wrongSubmission($studentB, $mission);

        $report = $this->report(null, ['student_id' => $studentA->id]);
        $filters = $this->filters(['student_id' => $studentA->id]);

        // Student-grained sections narrow to A: one completion, no wrong rows.
        $this->assertSame(1, $report['learning']['completed_challenges']);
        $this->assertSame(1, $report['learning']['active_students']);
        $this->assertSame(1, $report['period']['completions']);
        $this->assertSame(0, $report['period']['wrong_submissions']);

        // Composed sections follow their owning services under the same focus.
        $this->assertSame(app(ChallengeAnalyticsService::class)->summarize(collect([$studentA->id]), null, $filters), $report['challenges']);
        $this->assertSame(app(AssessmentAnalyticsService::class)->summarize(collect([$studentA->id]), null, $filters), $report['assessments']);
    }

    public function test_course_filter_outside_scope_fails_closed(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);

        // Authorized scope holds A only; requesting B fails closed.
        $report = $this->report([$courseA->id], ['course_id' => $courseB->id]);

        $this->assertSame(['courses' => 0, 'sections' => 0, 'challenges' => 0, 'boss_challenges' => 0], $report['catalog']);
        $this->assertSame(0, $report['learning']['course_completions']);
        $this->assertSame(0, $report['learning']['completed_challenges']);
        $this->assertSame(0, $report['challenges']['completions']);
        $this->assertSame(0, $report['challenges']['attempts']);
        $this->assertSame(0, $report['assessments']['attempts']);
        $this->assertSame(0, $report['period']['completions']);
    }

    public function test_invalid_status_student_and_course_filters_are_rejected(): void
    {
        $teacher = User::factory()->teacher()->create();

        foreach ([
            ['status' => 'active'],
            ['status' => 'completed'],
            ['student_id' => 999999],
            ['student_id' => $teacher->id],
            ['course_id' => 999999],
        ] as $input) {
            try {
                $this->filters($input);
                $this->fail('Expected ValidationException for '.json_encode($input));
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
    }

    public function test_live_metadata_edits_leave_stored_evidence_untouched(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->assessment($course);
        $this->complete($student, $mission);
        $this->wrongSubmission($student, $mission);
        $this->attemptAssessment($student, $course, 'SIGNAL answer');

        $before = $this->report();

        $course->update(['name' => 'Renamed Course']);
        $mission->update(['title' => 'Renamed Mission', 'difficulty' => 'HARD']);

        $after = $this->report();

        // Stored evidence is historical fact: counts never move. The
        // per-challenge title/difficulty honestly follow the live mission
        // row as descriptive text, so they are compared separately.
        $this->assertSame($before['period'], $after['period']);
        $this->assertSame($before['learning'], $after['learning']);
        $this->assertSame($before['assessments'], $after['assessments']);
        $this->assertSame($before['gamification'], $after['gamification']);

        $this->assertSame($before['challenges']['completions'], $after['challenges']['completions']);
        $this->assertSame($before['challenges']['attempts'], $after['challenges']['attempts']);
        $this->assertSame($before['challenges']['failures'], $after['challenges']['failures']);
        $this->assertSame($before['challenges']['completion_rate'], $after['challenges']['completion_rate']);
        $this->assertSame($before['challenges']['average_attempts'], $after['challenges']['average_attempts']);
        $this->assertSame('Renamed Mission', $after['challenges']['by_challenge'][0]['title']);
        $this->assertSame('HARD', $after['challenges']['by_challenge'][0]['difficulty']);
        $this->assertSame(1, $after['challenges']['by_challenge'][0]['completions']);
    }

    public function test_classroom_enrollment_does_not_distort_fleet_report(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($student, $mission);

        $before = $this->report();

        $this->classroomFor($teacher, [$student], [$course]);

        $after = $this->report();

        $this->assertSame($before, $after);
    }

    public function test_gamification_and_users_stay_labeled_fleet_under_student_filter(): void
    {
        $studentA = User::factory()->create(['role' => 'student']);
        $studentB = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->assessment($course);

        $this->complete($studentA, $mission);
        $this->wrongSubmission($studentA, $mission);
        $this->complete($studentB, $mission);
        $this->attemptAssessment($studentA, $course, 'SIGNAL answer');

        $xp = app(XpService::class);

        // The two learners genuinely differ: only A holds a wrong-submission
        // deduction and an assessment-pass award.
        $this->assertNotSame($xp->balance($studentA), $xp->balance($studentB));

        $plain = $this->report();
        $filtered = $this->report(null, ['student_id' => $studentA->id]);

        // No student-scoped gamification summary exists, so the subsection
        // stays fleet — and says so on its face instead of implying focus.
        $this->assertSame('fleet', $filtered['gamification']['scope']);
        $this->assertSame($plain['gamification'], $filtered['gamification']);

        // Same treatment for the fleet account facts.
        $this->assertSame('fleet', $filtered['users']['scope']);
        $this->assertSame($plain['users'], $filtered['users']);

        // While every student-relevant section genuinely narrows to A.
        $this->assertSame(1, $filtered['learning']['completed_challenges']);
        $this->assertSame(1, $filtered['period']['completions']);
        $this->assertSame(1, $filtered['period']['wrong_submissions']);
    }

    public function test_active_students_follow_fixed_window_not_report_range(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($student, $mission);

        // A range with no activity in it: the period empties, but engagement
        // stays on its own fixed authoritative window.
        $report = $this->report(null, ['from' => '2026-04-01', 'to' => '2026-04-30']);

        $this->assertSame(0, $report['period']['completions']);
        $this->assertSame(1, $report['learning']['active_students']);
        $this->assertSame(
            TeacherDashboardService::ACTIVE_WINDOW_DAYS,
            $report['learning']['active_students_window_days']
        );
    }

    public function test_query_count_stays_bounded_as_history_grows(): void
    {
        $studentA = User::factory()->create(['role' => 'student']);
        $studentB = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');

        foreach (range(1, 3) as $i) {
            $mission = $this->mission($course, "M{$i}");
            $this->complete($studentA, $mission);
            $this->complete($studentB, $mission);
        }

        $service = app(AdminSystemReportService::class);
        $queries = $this->countQueries(fn () => $service->forSystem());

        $this->assertLessThanOrEqual(60, $queries);

        $courseB = $this->course('beta', 'Beta');

        foreach (range(1, 6) as $i) {
            $mission = $this->mission($courseB, "B{$i}");
            $this->complete($studentA, $mission);
            $this->complete($studentB, $mission);
        }

        $grown = $this->countQueries(fn () => $service->forSystem());

        $this->assertLessThanOrEqual($queries + 8, $grown);
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
