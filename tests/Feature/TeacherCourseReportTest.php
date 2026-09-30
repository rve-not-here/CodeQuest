<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\Skill;
use App\Models\User;
use App\Services\AssessmentAnalyticsService;
use App\Services\AssessmentService;
use App\Services\ChallengeAnalyticsService;
use App\Services\CompetencyService;
use App\Services\CourseAnalyticsService;
use App\Services\MissionService;
use App\Services\TeacherCourseReportService;
use App\Services\XpService;
use App\Support\ReportFilters;
use Database\Seeders\AchievementSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-1004 teacher course report. A hand-built five-state lifecycle fixture
 * produced entirely through real domain actions, with every aggregate
 * reconciled against its authoritative owner.
 */
class TeacherCourseReportTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    private static int $orderSequence = 11000;

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

    private function mission(Course $course, string $title, array $skillKeys = []): Mission
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

        if ($skillKeys !== []) {
            $ids = [];

            foreach ($skillKeys as $key) {
                $ids[] = Skill::query()->firstOrCreate(['key' => $key], ['label' => $key])->id;
            }

            $mission->skills()->sync($ids);
        }

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

    private function wrongSubmission(User $user, Mission $mission): void
    {
        DB::table('the404_xp_transactions')->insert([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'amount' => -10,
            'type' => XpService::TYPE_WRONG_SUBMISSION,
            'description' => 'Wrong submission on mission: '.$mission->title,
            'created_at' => now()->toDateTimeString(),
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

    private function report(User $teacher, Course $course, array $input = []): array
    {
        return app(TeacherCourseReportService::class)->forTeacherCourse(
            $teacher,
            $course,
            $this->filters($input),
        );
    }

    /**
     * Five students driven through real domain actions into five lifecycle
     * positions: enrolled-but-idle, partial, all-missions-no-attempt,
     * attempted-but-failed, passed.
     *
     * @return array{teacher: User, course: Course, students: array<string, User>}
     */
    private function lifecycleFixture(): array
    {
        $teacher = User::factory()->teacher()->create();
        $students = [
            'idle' => User::factory()->create(['role' => 'student']),
            'partial' => User::factory()->create(['role' => 'student']),
            'ready' => User::factory()->create(['role' => 'student']),
            'failed' => User::factory()->create(['role' => 'student']),
            'passed' => User::factory()->create(['role' => 'student']),
        ];

        $course = $this->course('alpha', 'Alpha');
        $first = $this->mission($course, 'Alpha One', ['html.headings']);
        $second = $this->mission($course, 'Alpha Two', ['html.headings']);
        $this->assessment($course);

        $this->classroomFor($teacher, array_values($students), [$course]);

        $this->complete($students['partial'], $first);
        $this->wrongSubmission($students['partial'], $first);
        $this->complete($students['ready'], $first);
        $this->complete($students['ready'], $second);
        $this->complete($students['failed'], $first);
        $this->complete($students['failed'], $second);
        $this->attemptAssessment($students['failed'], $course, 'NOPE');
        $this->complete($students['passed'], $first);
        $this->complete($students['passed'], $second);
        $this->attemptAssessment($students['passed'], $course, 'SIGNAL answer');

        return compact('teacher', 'course', 'students');
    }

    public function test_lifecycle_buckets_match_hand_computed_states(): void
    {
        ['teacher' => $teacher, 'course' => $course] = $this->lifecycleFixture();

        $report = $this->report($teacher, $course);
        $lifecycle = $report['lifecycle'];

        // Five authorized students: 1 idle, 1 partial, 1 ready, 1 failed
        // (all missions done, so ready by the real gate), 1 passed.
        $this->assertSame(5, $lifecycle['participating']);
        $this->assertSame(4, $lifecycle['engaged']);
        $this->assertSame(1, $lifecycle['not_started']);
        $this->assertSame(1, $lifecycle['in_progress']);
        $this->assertSame(2, $lifecycle['assessment_ready']);
        $this->assertSame(1, $lifecycle['completed']);

        // Exclusive buckets sum to the participating population.
        $this->assertSame(
            $lifecycle['participating'],
            $lifecycle['not_started'] + $lifecycle['in_progress']
                + $lifecycle['assessment_ready'] + $lifecycle['completed']
        );

        // Reconciliation against the owning analytics row.
        $expected = app(CourseAnalyticsService::class)->overview()->firstWhere('course.id', $course->id);
        $this->assertSame($expected['buckets'], [
            'completed' => $lifecycle['completed'],
            'in_progress' => $lifecycle['in_progress'],
            'assessment_ready' => $lifecycle['assessment_ready'],
            'not_started' => $lifecycle['not_started'],
        ]);
        $this->assertSame($expected['avg_completion'], $lifecycle['average_completion']);
        $this->assertSame($expected['pass_rate'], $lifecycle['pass_rate']);
    }

    public function test_challenge_assessment_and_period_match_hand_computed_evidence(): void
    {
        ['teacher' => $teacher, 'course' => $course, 'students' => $students] = $this->lifecycleFixture();

        $report = $this->report($teacher, $course);
        $population = collect(array_map(fn (User $user): int => $user->id, array_values($students)))->sort()->values();
        $scope = collect([$course->id]);

        // Composed subsections equal their owning services verbatim.
        $this->assertSame(app(ChallengeAnalyticsService::class)->summarize($population, $scope, $this->filters()), $report['challenges']);
        $this->assertSame(app(AssessmentAnalyticsService::class)->summarize($population, $scope, $this->filters()), $report['assessments']);

        // Hand-computed: 7 mission completions, 1 wrong submission.
        $this->assertSame(7, $report['challenges']['completions']);
        $this->assertSame(1, $report['challenges']['failures']);
        $this->assertSame(7, $report['period']['completions']);
        $this->assertSame(1, $report['period']['wrong_submissions']);

        // Two assessment attempts (failed + passed), one pass.
        $this->assertSame(2, $report['assessments']['attempts']);
        $this->assertSame(1, $report['assessments']['passes']);
        $this->assertSame(2, $report['period']['assessment_attempts']);
        $this->assertSame(1, $report['period']['assessment_passes']);
    }

    public function test_competency_summary_reconciles_per_student_state(): void
    {
        ['teacher' => $teacher, 'course' => $course, 'students' => $students] = $this->lifecycleFixture();

        $report = $this->report($teacher, $course);
        $competency = $report['competency'];

        $this->assertCount(5, $competency['students']);

        foreach ($competency['students'] as $row) {
            $student = User::query()->find($row['student_id']);
            $expected = app(CompetencyService::class)->overview($student, collect([$course->id]))
                ->firstWhere('course.id', $course->id);
            $this->assertSame($expected['state'], $row['state']);
            $this->assertSame($expected['percent'], $row['percent']);
        }

        // Counts by state sum to the participating population; the passed
        // student demonstrates, the idle one has not started.
        $this->assertSame(5, array_sum($competency['by_state']));
        $byStudent = collect($competency['students'])->keyBy('student_id');
        $this->assertSame('demonstrated', $byStudent[$students['passed']->id]['state']);
        $this->assertSame('not_started', $byStudent[$students['idle']->id]['state']);
    }

    public function test_empty_authorized_course_reports_zeros_and_nulls(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->course('alpha', 'Alpha');
        $this->mission($course, 'Alpha One');
        $this->assessment($course);

        $this->classroomFor($teacher, [], [$course]);

        $report = $this->report($teacher, $course);
        $lifecycle = $report['lifecycle'];

        $this->assertSame($course->id, $report['course']['course_id']);
        $this->assertSame(0, $lifecycle['participating']);
        $this->assertSame(0, $lifecycle['engaged']);
        $this->assertSame(0, $lifecycle['not_started']);
        $this->assertSame(0, $lifecycle['in_progress']);
        $this->assertSame(0, $lifecycle['assessment_ready']);
        $this->assertSame(0, $lifecycle['completed']);
        $this->assertNull($lifecycle['average_completion']);
        $this->assertNull($lifecycle['pass_rate']);
        $this->assertSame([], $report['competency']['students']);
        $this->assertSame([], $report['competency']['by_state']);
        $this->assertSame(0, $report['period']['completions']);
    }

    public function test_unassigned_course_is_denied_before_composition(): void
    {
        $teacher = User::factory()->teacher()->create();
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $this->mission($courseA, 'Alpha One');
        $this->mission($courseB, 'Beta One');

        $this->classroomFor($teacher, [], [$courseA]);

        $this->expectException(AuthorizationException::class);
        $this->report($teacher, $courseB);
    }

    public function test_inactive_classroom_revokes_course_access(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');

        $classroom = $this->classroomFor($teacher, [$student], [$course]);
        $this->complete($student, $mission);

        $this->assertSame(1, $this->report($teacher, $course)['lifecycle']['participating']);

        $classroom->update(['status' => Classroom::STATUS_INACTIVE]);

        $this->expectException(AuthorizationException::class);
        $this->report($teacher, $course);
    }

    public function test_cross_course_isolation(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');

        // S learns in both rooms; each teacher's aggregate sees one course.
        $this->classroomFor($teacherA, [$student], [$courseA]);
        $this->classroomFor($teacherB, [$student], [$courseB]);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);
        $this->wrongSubmission($student, $missionB);

        $reportA = $this->report($teacherA, $courseA);
        $reportB = $this->report($teacherB, $courseB);

        $this->assertSame($courseA->id, $reportA['course']['course_id']);
        $this->assertSame(1, $reportA['lifecycle']['participating']);
        $this->assertSame(1, $reportA['period']['completions']);
        $this->assertSame(0, $reportA['period']['wrong_submissions']);

        $this->assertSame($courseB->id, $reportB['course']['course_id']);
        $this->assertSame(1, $reportB['period']['completions']);
        $this->assertSame(1, $reportB['period']['wrong_submissions']);

        // A teacher with no relationship to the other course is denied it.
        try {
            $this->report($teacherA, $courseB);
            $this->fail('Expected AuthorizationException for unrelated course');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
    }

    public function test_course_filter_match_narrows_and_mismatch_fails_closed(): void
    {
        ['teacher' => $teacher, 'course' => $course] = $this->lifecycleFixture();
        $other = $this->course('zz', 'Other');

        $matched = $this->report($teacher, $course, ['course_id' => $course->id]);
        $this->assertSame(5, $matched['lifecycle']['participating']);

        $closed = $this->report($teacher, $course, ['course_id' => $other->id]);
        $this->assertSame($course->id, $closed['course']['course_id']);
        $this->assertSame(0, $closed['lifecycle']['participating']);
        $this->assertSame(0, $closed['lifecycle']['completed']);
        $this->assertSame([], $closed['competency']['students']);
        $this->assertSame(0, $closed['period']['completions']);
    }

    public function test_student_filter_narrows_population_and_outsider_fails_closed(): void
    {
        ['teacher' => $teacher, 'course' => $course, 'students' => $students] = $this->lifecycleFixture();
        $outsider = User::factory()->create(['role' => 'student']);

        $focused = $this->report($teacher, $course, ['student_id' => $students['passed']->id]);
        $this->assertSame(1, $focused['lifecycle']['participating']);
        $this->assertSame(1, $focused['lifecycle']['completed']);
        $this->assertSame(1, $focused['period']['assessment_passes']);

        $closed = $this->report($teacher, $course, ['student_id' => $outsider->id]);
        $this->assertSame(0, $closed['lifecycle']['participating']);
        $this->assertSame([], $closed['competency']['students']);
        $this->assertSame(0, $closed['period']['completions']);
    }

    public function test_date_range_narrows_activity_without_touching_lifecycle(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $april = $this->mission($course, 'April Mission');
        $may = $this->mission($course, 'May Mission');

        $this->classroomFor($teacher, [$student], [$course]);
        $this->complete($student, $april);
        $this->complete($student, $may);

        DB::table('the404_progress')->where('mission_id', $april->id)->update(['completed_at' => '2026-04-10 09:00:00']);
        DB::table('the404_progress')->where('mission_id', $may->id)->update(['completed_at' => '2026-05-10 09:00:00']);

        $report = $this->report($teacher, $course, ['from' => '2026-05-01', 'to' => '2026-05-31']);

        // Lifecycle stays cumulative; only movement narrows to May.
        $this->assertSame(1, $report['lifecycle']['participating']);
        $this->assertSame(1, $report['lifecycle']['engaged']);
        $this->assertSame(1, $report['period']['completions']);
        $this->assertSame('2026-05-01 00:00:00', $report['period']['from']);
        $this->assertSame('2026-06-01 00:00:00', $report['period']['to_exclusive']);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        foreach ([
            ['status' => 'active'],
            ['student_id' => 999999],
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

    public function test_stored_verdicts_survive_live_rule_and_label_edits(): void
    {
        ['teacher' => $teacher, 'course' => $course] = $this->lifecycleFixture();
        $mission = Mission::query()->where('course_id', $course->id)->orderBy('id')->firstOrFail();
        $assessment = Assessment::query()->where('course_id', $course->id)->firstOrFail();

        $before = $this->report($teacher, $course);

        // Harden the live gate and rename content after evidence exists.
        $assessment->update(['passing_score' => 100]);
        $mission->update(['title' => 'Renamed Mission', 'difficulty' => 'HARD']);
        Skill::query()->where('key', 'html.headings')->update(['label' => 'Renamed Headings']);

        $after = $this->report($teacher, $course);

        // Stored outcomes are historical fact: completion, passes, pass
        // rate, and period movement do not move with the live threshold.
        $this->assertSame($before['lifecycle'], $after['lifecycle']);
        $this->assertSame($before['period'], $after['period']);
        $this->assertSame(1, $after['assessments']['passes']);

        // Competency still equals its authority under the same scope.
        $expected = app(CourseAnalyticsService::class)->overview(null, collect([$course->id]));
        $this->assertSame($expected->firstWhere('course.id', $course->id)['buckets'], [
            'completed' => $after['lifecycle']['completed'],
            'in_progress' => $after['lifecycle']['in_progress'],
            'assessment_ready' => $after['lifecycle']['assessment_ready'],
            'not_started' => $after['lifecycle']['not_started'],
        ]);
    }

    public function test_query_count_stays_bounded_as_population_grows(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $classroom = $this->classroomFor($teacher, [], [$course]);

        $service = app(TeacherCourseReportService::class);
        $runFor = function (int $students) use ($teacher, $course, $mission, $classroom, $service): int {
            $fresh = User::factory()->count($students)->create(['role' => 'student'])->all();
            $classroom->students()->syncWithoutDetaching(collect($fresh)->pluck('id')->all());

            foreach ($fresh as $student) {
                $this->complete($student, $mission);
            }

            return $this->countQueries(fn () => $service->forTeacherCourse($teacher, $course));
        };

        // Cumulative enrollment: 1, then 10, then 50 students total.
        $one = $runFor(1);
        $ten = $runFor(9);
        $fifty = $runFor(40);

        $this->assertLessThanOrEqual(80, $one);
        $this->assertLessThanOrEqual($one + 6, $ten);
        $this->assertLessThanOrEqual($one + 6, $fifty);
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
