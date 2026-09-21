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
use App\Services\CompetencyReportService;
use App\Services\CompetencyService;
use App\Services\MissionService;
use App\Services\ReportAuthorizationService;
use App\Services\ReportCsvExporter;
use App\Services\ReportPdfExporter;
use App\Services\StudentProgressReportService;
use App\Services\TeacherCourseReportService;
use App\Services\TeacherStudentReportService;
use App\Support\ReportFilters;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-1011 report performance regression suite. Every scale test measures
 * exact query counts at two or three fixture sizes and asserts a strict
 * fixed delta: population growth may return more rows, never more queries.
 * A companion duplicate-query tripwire fails any report that repeats the
 * same SQL beyond a small fixed allowance.
 */
class ReportPerformanceTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    private static int $orderSequence = 19000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);
    }

    private function course(string $slug): Course
    {
        self::$orderSequence++;

        $course = new Course([
            'slug' => $slug,
            'name' => 'Course '.self::$orderSequence,
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

    /**
     * @return array<string, int> SQL fingerprint => execution count
     */
    private function queryFingerprint(callable $fn): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $fn();
        $log = DB::getQueryLog();
        DB::flushQueryLog();
        DB::disableQueryLog();

        $counts = [];

        foreach ($log as $entry) {
            $sql = preg_replace('/\s+/', ' ', (string) $entry['query']);
            $counts[$sql] = ($counts[$sql] ?? 0) + 1;
        }

        arsort($counts);

        return $counts;
    }

    public function test_student_progress_report_bounded_over_courses(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $service = app(StudentProgressReportService::class);
        $counts = [];

        foreach ([1, 5, 20] as $total) {
            while (Course::query()->count() < $total) {
                $course = $this->course('c'.self::$orderSequence);
                $mission = $this->mission($course, 'M1');
                $this->assessment($course);
                $this->complete($student, $mission);
            }

            $counts[$total] = $this->countQueries(fn () => $service->forStudent($student));
        }

        $this->assertLessThanOrEqual(120, $counts[1]);
        $this->assertLessThanOrEqual($counts[1] + 8, $counts[5]);
        $this->assertLessThanOrEqual($counts[1] + 8, $counts[20]);
    }

    public function test_teacher_student_report_bounded_over_courses(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $service = app(TeacherStudentReportService::class);
        $counts = [];
        $classroom = $this->classroomFor($teacher, [$student], []);

        // Unrelated courses outside teacher scope must not move the cost.
        $outsiders = [];

        foreach ([1, 5, 20] as $total) {
            while (Course::query()->count() < $total + 5) {
                $course = $this->course('c'.self::$orderSequence);
                $mission = $this->mission($course, 'M1');
                $this->assessment($course);
                $this->complete($student, $mission);

                if (count($outsiders) < 5) {
                    $outsiders[] = $course;
                } else {
                    $classroom->courses()->syncWithoutDetaching([$course->id]);
                }
            }

            $counts[$total] = $this->countQueries(fn () => $service->forTeacherStudent($teacher, $student));
        }

        $this->assertLessThanOrEqual(140, $counts[1]);
        $this->assertLessThanOrEqual($counts[1] + 8, $counts[5]);
        $this->assertLessThanOrEqual($counts[1] + 8, $counts[20]);
    }

    public function test_teacher_course_report_bounded_over_students(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->course('alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->assessment($course);
        $classroom = $this->classroomFor($teacher, [], [$course]);
        $service = app(TeacherCourseReportService::class);
        $counts = [];

        foreach ([1, 10, 50] as $total) {
            while (User::query()->where('role', 'student')->count() < $total) {
                $student = User::factory()->create(['role' => 'student']);
                $classroom->students()->syncWithoutDetaching([$student->id]);
                $this->complete($student, $mission);
            }

            $counts[$total] = $this->countQueries(fn () => $service->forTeacherCourse($teacher, $course));
        }

        $this->assertLessThanOrEqual(80, $counts[1]);
        $this->assertLessThanOrEqual($counts[1] + 6, $counts[10]);
        $this->assertLessThanOrEqual($counts[1] + 6, $counts[50]);
    }

    public function test_admin_system_report_bounded_over_students(): void
    {
        $course = $this->course('alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->assessment($course);
        $service = app(AdminSystemReportService::class);
        $counts = [];

        foreach ([10, 50, 100] as $total) {
            while (User::query()->where('role', 'student')->count() < $total) {
                $student = User::factory()->create(['role' => 'student']);
                $this->complete($student, $mission);
            }

            if ($total === 10) {
                $first = User::query()->where('role', 'student')->firstOrFail();
                $this->attemptAssessment($first, $course, 'SIGNAL answer');
            }

            $counts[$total] = $this->countQueries(fn () => $service->forSystem());
        }

        $this->assertLessThanOrEqual(100, $counts[10]);
        $this->assertLessThanOrEqual($counts[10] + 6, $counts[50]);
        $this->assertLessThanOrEqual($counts[10] + 6, $counts[100]);
    }

    public function test_analytics_services_bounded_over_volume(): void
    {
        $course = $this->course('alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->assessment($course);
        $challenges = app(ChallengeAnalyticsService::class);
        $assessments = app(AssessmentAnalyticsService::class);
        $challengeCounts = [];
        $assessmentCounts = [];

        foreach ([5, 25, 60] as $total) {
            while (User::query()->where('role', 'student')->count() < $total) {
                $student = User::factory()->create(['role' => 'student']);
                $this->complete($student, $mission);
            }

            if ($total === 5) {
                $first = User::query()->where('role', 'student')->firstOrFail();
                $this->attemptAssessment($first, $course, 'SIGNAL answer');
            }

            $challengeCounts[$total] = $this->countQueries(fn () => $challenges->summarize());
            $assessmentCounts[$total] = $this->countQueries(fn () => $assessments->summarize());
        }

        $this->assertLessThanOrEqual($challengeCounts[5] + 4, $challengeCounts[25]);
        $this->assertLessThanOrEqual($challengeCounts[5] + 4, $challengeCounts[60]);
        $this->assertLessThanOrEqual($assessmentCounts[5] + 4, $assessmentCounts[25]);
        $this->assertLessThanOrEqual($assessmentCounts[5] + 4, $assessmentCounts[60]);
    }

    public function test_competency_report_and_batch_bounded_over_volume(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $competency = app(CompetencyReportService::class);
        $batch = app(CompetencyService::class);
        $reportCounts = [];

        foreach ([2, 8, 20] as $total) {
            while (Course::query()->count() < $total) {
                $course = $this->course('c'.self::$orderSequence);
                $mission = $this->mission($course, 'M1');
                $this->complete($student, $mission);
            }

            $reportCounts[$total] = $this->countQueries(fn () => $competency->forStudent($student));
        }

        $users = User::factory()->count(30)->create(['role' => 'student']);
        $batchCount = $this->countQueries(fn () => $batch->overviewForStudents($users));
        $singleCount = $this->countQueries(fn () => $batch->overview($users->firstOrFail()));

        // Thirty students cost barely more than one.
        $this->assertLessThanOrEqual($singleCount + 4, $batchCount);
    }

    public function test_export_serialization_adds_zero_queries(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($student, $mission);

        $report = app(StudentProgressReportService::class)->forStudent($student, null, null);
        $csv = app(ReportCsvExporter::class);
        $pdf = app(ReportPdfExporter::class);
        $view = $pdf->studentProgressView($report);

        $this->assertSame(0, $this->countQueries(fn () => $csv->courses($report['progress']['courses'])));
        $this->assertSame(0, $this->countQueries(fn () => $csv->skills($report['competency']['skills'])));
        $this->assertSame(0, $this->countQueries(fn () => $csv->metrics($report['period'])));
        $this->assertSame(0, $this->countQueries(fn () => $pdf->render($view)));
        $this->assertSame(0, $this->countQueries(fn () => $pdf->pdf($pdf->render($view))));
    }

    public function test_authorization_checks_stay_constant(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha');
        $authorization = app(ReportAuthorizationService::class);

        $this->classroomFor($teacher, [$student], [$course]);

        // Noise: many unrelated classrooms, students, and courses.
        foreach (range(1, 10) as $i) {
            $noisyTeacher = User::factory()->teacher()->create();
            $noisyCourse = $this->course('noise'.$i);
            $noisyStudent = User::factory()->create(['role' => 'student']);
            $this->classroomFor($noisyTeacher, [$noisyStudent], [$noisyCourse]);
        }

        $this->assertLessThanOrEqual(8, $this->countQueries(fn () => $authorization->canViewStudentReport($teacher, $student)));
        $this->assertLessThanOrEqual(8, $this->countQueries(fn () => $authorization->reportCourseIds($teacher, $student)));
        $this->assertLessThanOrEqual(8, $this->countQueries(fn () => $authorization->canViewCourseReport($teacher, $course)));

        $admin = User::factory()->admin()->create();
        $this->assertLessThanOrEqual(4, $this->countQueries(fn () => $authorization->canViewSystemReport($admin)));
    }

    public function test_no_report_repeats_sql_beyond_fixed_allowance(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->assessment($course);

        $this->classroomFor($teacher, [$student], [$course]);
        $this->complete($student, $mission);
        $this->attemptAssessment($student, $course, 'SIGNAL answer');

        $prints = [
            'student' => $this->queryFingerprint(fn () => app(StudentProgressReportService::class)->forStudent($student)),
            'teacher-student' => $this->queryFingerprint(fn () => app(TeacherStudentReportService::class)->forTeacherStudent($teacher, $student)),
            'teacher-course' => $this->queryFingerprint(fn () => app(TeacherCourseReportService::class)->forTeacherCourse($teacher, $course)),
            'admin' => $this->queryFingerprint(function (): void {
                app(AdminSystemReportService::class)->forSystem(null, $this->filters());
            }),
        ];

        foreach ($prints as $name => $fingerprint) {
            $worst = $fingerprint === [] ? 0 : max($fingerprint);

            $this->assertLessThanOrEqual(
                3,
                $worst,
                "Report {$name} repeats one SQL {$worst} times: ".implode(', ', array_keys(array_filter($fingerprint, fn (int $count): bool => $count > 3))),
            );
        }
    }
}
