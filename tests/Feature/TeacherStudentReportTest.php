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
use App\Services\MissionService;
use App\Services\RecommendationService;
use App\Services\TeacherStudentReportService;
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
 * US-1003 teacher student report. Real classrooms, real enrollments, and
 * hand-computed fixtures prove the teacher sees exactly the authorized
 * student's authorized courses — and nothing else.
 */
class TeacherStudentReportTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    private static int $orderSequence = 9000;

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

    private function report(User $teacher, User $student, array $input = []): array
    {
        return app(TeacherStudentReportService::class)->forTeacherStudent(
            $teacher,
            $student,
            $this->filters($input),
        );
    }

    public function test_authorized_teacher_sees_only_shared_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One', ['html.headings']);
        $missionB = $this->mission($courseB, 'Beta One', ['css.color']);
        $this->assessment($courseA);
        $this->assessment($courseB);

        $this->classroomFor($teacher, [$student], [$courseA]);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);
        $this->wrongSubmission($student, $missionA);
        $this->attemptAssessment($student, $courseA, 'SIGNAL answer');

        $report = $this->report($teacher, $student);

        $this->assertSame($teacher->id, $report['teacher_id']);
        $this->assertSame($student->id, $report['student_id']);
        $this->assertSame([$courseA->id], $report['course_ids']);

        // Only the shared course renders; B's completion never appears.
        $this->assertCount(1, $report['progress']['courses']);
        $this->assertSame($courseA->id, $report['progress']['courses'][0]['course_id']);

        // A is complete (assessment passed) within the authorized scope, so
        // there is no current course — and incomplete out-of-scope B must
        // not become current either.
        $this->assertNull($report['progress']['current_course_id']);
        $this->assertNull($report['progress']['next_mission']);
        $this->assertSame(['html.headings'], collect($report['competency']['skills'])->pluck('key')->all());
        $this->assertSame(1, $report['period']['completions']);
        $this->assertSame(1, $report['period']['wrong_submissions']);
        $this->assertSame(1, $report['period']['assessment_attempts']);
        $this->assertSame(1, $report['period']['assessment_passes']);

        // Teacher-visible sections equal the authoritative scoped outputs.
        $ids = collect([$student->id]);
        $scope = collect([$courseA->id]);
        $this->assertSame(app(ChallengeAnalyticsService::class)->summarize($ids, $scope, $this->filters()), $report['challenges']);
        $this->assertSame(app(AssessmentAnalyticsService::class)->summarize($ids, $scope, $this->filters()), $report['assessments']);
        $this->assertEquals(app(RecommendationService::class)->recommendations($student, $scope)->all(), $report['recommendations']);

        // Student-private sections are absent, not merely emptied.
        $this->assertArrayNotHasKey('xp', $report);
        $this->assertArrayNotHasKey('achievements', $report);
        $this->assertArrayNotHasKey('timeline', $report);
    }

    public function test_multiple_shared_courses_all_render(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');

        $this->classroomFor($teacher, [$student], [$courseA, $courseB]);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);

        $report = $this->report($teacher, $student);

        $this->assertCount(2, $report['progress']['courses']);
        $this->assertSame(2, $report['period']['completions']);
    }

    public function test_cross_classroom_isolation(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');

        // S learns in both rooms; each teacher sees only their own room.
        $this->classroomFor($teacherA, [$student], [$courseA]);
        $this->classroomFor($teacherB, [$student], [$courseB]);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);

        $reportA = $this->report($teacherA, $student);
        $reportB = $this->report($teacherB, $student);

        $this->assertSame([$courseA->id], collect($reportA['progress']['courses'])->pluck('course_id')->all());
        $this->assertSame(1, $reportA['period']['completions']);
        $this->assertSame([$courseB->id], collect($reportB['progress']['courses'])->pluck('course_id')->all());
        $this->assertSame(1, $reportB['period']['completions']);

        // No B rows leak into A's challenge breakdown and vice versa.
        $this->assertSame([$courseA->id], collect($reportA['challenges']['by_challenge'])->pluck('course_id')->unique()->all());
        $this->assertSame([$courseB->id], collect($reportB['challenges']['by_challenge'])->pluck('course_id')->unique()->all());
    }

    public function test_outsider_teacher_is_denied_before_composition(): void
    {
        $teacher = User::factory()->teacher()->create();
        $outsider = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');

        $this->classroomFor($teacher, [$student], [$course]);
        $this->complete($student, $mission);

        $this->expectException(AuthorizationException::class);
        $this->report($outsider, $student);
    }

    public function test_inactive_classroom_revokes_visibility(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');

        $classroom = $this->classroomFor($teacher, [$student], [$course]);
        $this->complete($student, $mission);

        // Visible while the classroom is active.
        $this->assertNotEmpty($this->report($teacher, $student)['progress']['courses']);

        $classroom->update(['status' => Classroom::STATUS_INACTIVE]);

        $this->expectException(AuthorizationException::class);
        $this->report($teacher, $student);
    }

    public function test_non_student_targets_are_denied(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();
        $operator = User::factory()->create(['role' => 'operator']);
        $course = $this->course('alpha', 'Alpha');

        $this->classroomFor($teacher, [], [$course]);

        foreach ([$otherTeacher, $admin, $operator] as $target) {
            try {
                $this->report($teacher, $target);
                $this->fail('Expected AuthorizationException for role '.$target->role);
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_authorized_course_between_classrooms_but_no_shared_course_is_empty(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');

        // Enrolled together but the classroom carries no courses: the
        // teacher is authorized for the student yet the scope is empty.
        $this->classroomFor($teacher, [$student], []);
        $this->complete($student, $mission);

        $report = $this->report($teacher, $student);

        $this->assertSame([], $report['course_ids']);
        $this->assertSame([], $report['progress']['courses']);
        $this->assertNull($report['progress']['current_course_id']);
        $this->assertSame([], $report['competency']['skills']);
        $this->assertSame(0, $report['period']['completions']);
    }

    public function test_in_scope_course_filter_narrows_and_out_of_scope_fails_closed(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $courseC = $this->course('cc', 'Course C');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');
        $missionC = $this->mission($courseC, 'Gamma One');

        $this->classroomFor($teacher, [$student], [$courseA, $courseB]);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);
        $this->complete($student, $missionC);

        $narrowed = $this->report($teacher, $student, ['course_id' => $courseB->id]);
        $this->assertSame([$courseA->id, $courseB->id], $narrowed['course_ids']);
        $this->assertSame([$courseB->id], collect($narrowed['progress']['courses'])->pluck('course_id')->all());
        $this->assertSame(1, $narrowed['period']['completions']);

        // C is the student's course but outside the teacher's scope.
        $closed = $this->report($teacher, $student, ['course_id' => $courseC->id]);
        $this->assertSame([], $closed['progress']['courses']);
        $this->assertSame(0, $closed['period']['completions']);
    }

    public function test_matching_student_filter_preserves_and_mismatch_fails_closed(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');

        $this->classroomFor($teacher, [$student], [$course]);
        $this->complete($student, $mission);

        $plain = $this->report($teacher, $student);
        $matched = $this->report($teacher, $student, ['student_id' => $student->id]);
        $this->assertSame($plain, $matched);

        $closed = $this->report($teacher, $student, ['student_id' => $other->id]);
        $this->assertSame([], $closed['progress']['courses']);
        $this->assertSame([], $closed['competency']['skills']);
        $this->assertSame([], $closed['recommendations']);
        $this->assertSame(0, $closed['period']['completions']);
    }

    public function test_date_range_narrows_activity_without_touching_state(): void
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

        $report = $this->report($teacher, $student, ['from' => '2026-05-01', 'to' => '2026-05-31']);

        $this->assertSame(2, $report['progress']['courses'][0]['completed_missions']);
        $this->assertSame(1, $report['period']['completions']);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $teacher = User::factory()->teacher()->create();

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

    public function test_live_metadata_edits_keep_teacher_scope_correct(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One', ['html.headings']);
        $missionB = $this->mission($courseB, 'Beta One', ['css.color']);
        $this->assessment($courseA);

        $this->classroomFor($teacher, [$student], [$courseA]);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);
        $this->wrongSubmission($student, $missionA);
        $this->attemptAssessment($student, $courseA, 'SIGNAL answer');

        $before = $this->report($teacher, $student);

        $courseA->update(['name' => 'Renamed Course']);
        $missionA->update(['title' => 'Renamed Mission', 'difficulty' => 'HARD']);
        Skill::query()->where('key', 'html.headings')->update(['label' => 'Renamed Headings']);

        $after = $this->report($teacher, $student);

        // Stored evidence is historical fact; scope still excludes course B.
        $this->assertSame($before['period'], $after['period']);
        $this->assertSame([$courseA->id], collect($after['progress']['courses'])->pluck('course_id')->all());
        $this->assertSame('Renamed Headings', $after['competency']['skills'][0]['label']);

        $expected = app(CompetencyService::class)->skills($student, collect([$courseA->id]));
        $this->assertSame($expected[0]['percentage'], $after['competency']['skills'][0]['percentage']);
    }

    public function test_query_count_stays_bounded_as_history_grows(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');

        $this->classroomFor($teacher, [$student], [$course]);

        foreach (range(1, 3) as $i) {
            $mission = $this->mission($course, "M{$i}");
            $this->complete($student, $mission);
        }

        $service = app(TeacherStudentReportService::class);
        $queries = $this->countQueries(fn () => $service->forTeacherStudent($teacher, $student));

        $this->assertLessThanOrEqual(70, $queries);

        $courseB = $this->course('beta', 'Beta');

        foreach (range(1, 6) as $i) {
            $mission = $this->mission($courseB, "B{$i}");
            $this->complete($student, $mission);
        }

        // Course B sits outside the teacher's scope: history doubles while
        // the teacher-visible query count barely moves.
        $grown = $this->countQueries(fn () => $service->forTeacherStudent($teacher, $student));

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
