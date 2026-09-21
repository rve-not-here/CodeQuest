<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\Skill;
use App\Models\User;
use App\Services\AdminSystemReportService;
use App\Services\AssessmentService;
use App\Services\MissionService;
use App\Services\ReportCsvExporter;
use App\Services\StudentProgressReportService;
use App\Services\TeacherCourseReportService;
use App\Services\TeacherStudentReportService;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-1010 report exports (CSV). Every download is parsed back and compared
 * against the authoritative report service output it must reproduce, with
 * authorization, filter parity, and injection safety proven per role.
 */
class ReportExportTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    private static int $orderSequence = 15000;

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

    private function attemptAssessment(User $user, Course $course, string $code): void
    {
        $service = app(AssessmentService::class);
        $attempt = $service->beginAttempt($user, $course);
        $service->submitAttempt($user, $attempt, $code);
        $service->evaluateAttempt($user, $attempt);
    }

    /**
     * @return list<list<string>>
     */
    private function parseCsv(string $body): array
    {
        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);
        $handle = fopen('php://memory', 'r+b');
        fwrite($handle, $body);
        rewind($handle);

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null]) {
                continue;
            }

            $rows[] = array_map(fn ($cell): string => (string) $cell, $row);
        }

        fclose($handle);

        return $rows;
    }

    private function assertDownloadHeaders(string $disposition, string $filename): void
    {
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString($filename, $disposition);
    }

    public function test_student_self_courses_export_matches_report(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);
        $this->complete($student, $mission);

        $response = $this->actingAs($student)->get(route('export.progress'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertDownloadHeaders($response->headers->get('Content-Disposition'), "student-progress-{$student->id}.csv");

        $rows = $this->parseCsv($response->streamedContent());
        $this->assertSame(
            ['course_id', 'name', 'state', 'percent', 'completed_missions', 'total_missions', 'challenge_passed'],
            $rows[0]
        );

        $report = app(StudentProgressReportService::class)->forStudent($student, null, null);
        $expected = $report['progress']['courses'][0];
        $this->assertSame((string) $expected['course_id'], $rows[1][0]);
        $this->assertSame($expected['name'], $rows[1][1]);
        $this->assertSame($expected['state'], $rows[1][2]);
        $this->assertSame((string) $expected['percent'], $rows[1][3]);
        $this->assertSame($expected['challenge_passed'] ? 'true' : 'false', $rows[1][6]);
        $this->assertCount(count($report['progress']['courses']) + 1, $rows);
    }

    public function test_student_skills_and_period_datasets_match_report(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);
        $this->complete($student, $mission);

        $skills = $this->parseCsv($this->actingAs($student)->get(route('export.progress', ['dataset' => 'skills']))->streamedContent());
        $this->assertSame(
            ['key', 'label', 'percentage', 'state', 'weak', 'kc_correct', 'kc_total', 'challenges_completed', 'challenges_applicable'],
            $skills[0]
        );

        $report = app(StudentProgressReportService::class)->forStudent($student, null, null);
        $this->assertCount(count($report['competency']['skills']) + 1, $skills);
        $this->assertSame($report['competency']['skills'][0]['key'], $skills[1][0]);

        $period = $this->parseCsv($this->actingAs($student)->get(route('export.progress', ['dataset' => 'period']))->streamedContent());
        $this->assertSame(['metric', 'value'], $period[0]);
        $byMetric = collect($period)->skip(1)->mapWithKeys(fn (array $row): array => [$row[0] => $row[1]]);
        $this->assertSame((string) $report['period']['completions'], $byMetric['completions']);
    }

    public function test_student_cannot_export_another_learner(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($other, $mission);

        // A mismatched student filter fails the composed report closed, so
        // the export carries headers but no other learner's rows.
        $response = $this->actingAs($student)->get(route('export.progress', ['student_id' => $other->id]));

        $response->assertOk();
        $rows = $this->parseCsv($response->streamedContent());
        $this->assertCount(1, $rows);
    }

    public function test_non_student_cannot_use_self_export(): void
    {
        $teacher = User::factory()->teacher()->create();
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($teacher)->get(route('export.progress'))->assertForbidden();
        $this->actingAs($operator)->get(route('export.progress'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('export.progress'))->assertRedirect(route('login'));
    }

    public function test_teacher_student_export_matches_scoped_report_without_private_sections(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');

        $this->classroomFor($teacher, [$student], [$courseA]);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);

        $response = $this->actingAs($teacher)->get(route('export.teacher-student', $student));

        $response->assertOk();
        $this->assertDownloadHeaders($response->headers->get('Content-Disposition'), "teacher-student-{$student->id}.csv");

        $rows = $this->parseCsv($response->streamedContent());
        $this->assertSame(
            ['course_id', 'name', 'state', 'percent', 'completed_missions', 'total_missions', 'challenge_passed'],
            $rows[0]
        );
        $this->assertCount(2, $rows);
        $this->assertSame((string) $courseA->id, $rows[1][0]);

        $report = app(TeacherStudentReportService::class)->forTeacherStudent($teacher, $student, null);
        $this->assertCount(count($report['progress']['courses']) + 1, $rows);

        // The teacher subset has no XP, achievement, or timeline columns.
        $this->assertStringNotContainsStringIgnoringCase('balance', $response->streamedContent());
        $this->assertStringNotContainsStringIgnoringCase('streak', $response->streamedContent());
    }

    public function test_teacher_outsider_is_denied_student_export(): void
    {
        $teacher = User::factory()->teacher()->create();
        $outsider = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');

        $this->classroomFor($teacher, [$student], [$course]);

        $this->actingAs($outsider)->get(route('export.teacher-student', $student))->assertForbidden();
    }

    public function test_teacher_student_export_rejects_non_student_target(): void
    {
        $teacher = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();
        $course = $this->course('alpha', 'Alpha');

        $this->classroomFor($teacher, [], [$course]);

        $this->actingAs($teacher)->get(route('export.teacher-student', $other))->assertNotFound();
    }

    public function test_teacher_course_export_matches_report(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $missionA = $this->mission($courseA, 'Alpha One');
        $missionB = $this->mission($courseB, 'Beta One');

        $this->classroomFor($teacher, [$student], [$courseA]);
        $this->complete($student, $missionA);
        $this->complete($student, $missionB);

        $response = $this->actingAs($teacher)->get(route('export.teacher-course', $courseA));

        $response->assertOk();
        $this->assertDownloadHeaders($response->headers->get('Content-Disposition'), "teacher-course-{$courseA->id}.csv");

        $rows = $this->parseCsv($response->streamedContent());
        $this->assertSame(['metric', 'value'], $rows[0]);

        $report = app(TeacherCourseReportService::class)->forTeacherCourse($teacher, $courseA, null);
        $byMetric = collect($rows)->skip(1)->mapWithKeys(fn (array $row): array => [$row[0] => $row[1]]);
        $this->assertSame((string) $report['lifecycle']['participating'], $byMetric['participating']);
        $this->assertSame((string) $report['lifecycle']['completed'], $byMetric['completed']);

        // Cross-course evidence never leaks: B's completion is invisible.
        $this->assertSame('1', $byMetric['participating']);

        $students = $this->parseCsv($this->actingAs($teacher)->get(route('export.teacher-course', [$courseA, 'dataset' => 'students']))->streamedContent());
        $this->assertSame(['student_id', 'state', 'percent'], $students[0]);
        $this->assertSame((string) $student->id, $students[1][0]);
    }

    public function test_teacher_unassigned_course_export_is_denied(): void
    {
        $teacher = User::factory()->teacher()->create();
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $this->mission($courseA, 'Alpha One');
        $this->mission($courseB, 'Beta One');

        $this->classroomFor($teacher, [], [$courseA]);

        $this->actingAs($teacher)->get(route('export.teacher-course', $courseB))->assertForbidden();
    }

    public function test_admin_system_export_matches_report_with_labels(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($student, $mission);

        $response = $this->actingAs($admin)->get(route('export.admin-system'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertDownloadHeaders($response->headers->get('Content-Disposition'), 'admin-system.csv');

        $rows = $this->parseCsv($response->streamedContent());
        $this->assertSame(['metric', 'value'], $rows[0]);

        $report = app(AdminSystemReportService::class)->forSystem(null, null);
        $byMetric = collect($rows)->skip(1)->mapWithKeys(fn (array $row): array => [$row[0] => $row[1]]);

        // Fleet scope and fixed-window labels travel explicitly in the file.
        $this->assertSame('fleet', $byMetric['users.scope']);
        $this->assertSame('fleet', $byMetric['gamification.scope']);
        $this->assertSame((string) $report['users']['total'], $byMetric['users.total']);
        $this->assertSame((string) $report['learning']['active_students_window_days'], $byMetric['learning.active_students_window_days']);
        $this->assertSame((string) $report['gamification']['awarded'], $byMetric['gamification.awarded']);
    }

    public function test_non_admin_cannot_use_system_export(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($teacher)->get(route('export.admin-system'))->assertForbidden();
        $this->actingAs($student)->get(route('export.admin-system'))->assertForbidden();
    }

    public function test_date_and_course_filters_reach_exports(): void
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

        $period = $this->parseCsv($this->actingAs($student)->get(
            route('export.progress', ['dataset' => 'period', 'from' => '2026-05-01', 'to' => '2026-05-31'])
        )->streamedContent());
        $byMetric = collect($period)->skip(1)->mapWithKeys(fn (array $row): array => [$row[0] => $row[1]]);
        $this->assertSame('1', $byMetric['completions']);

        $narrowed = $this->parseCsv($this->actingAs($student)->get(
            route('export.progress', ['course_id' => $courseB->id])
        )->streamedContent());
        $this->assertCount(2, $narrowed);
        $this->assertSame((string) $courseB->id, $narrowed[1][0]);
    }

    public function test_invalid_filters_and_format_are_rejected(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        // ReportFilters validation failures redirect under web conventions.
        $this->actingAs($student)->get(route('export.progress', ['from' => 'not-a-date']))->assertRedirect();
        $this->actingAs($student)->get(route('export.progress', ['status' => 'active']))->assertRedirect();

        // PDF is served through the hardened exporter; unknown formats
        // still fail envelope validation.
        $this->actingAs($student)->get(route('export.progress', ['format' => 'epub']))
            ->assertRedirect()
            ->assertSessionHasErrors('format');

        $this->actingAs($student)->get(route('export.progress', ['dataset' => 'grades']))
            ->assertRedirect()
            ->assertSessionHasErrors('dataset');
    }

    public function test_empty_report_exports_header_only(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $rows = $this->parseCsv($this->actingAs($student)->get(route('export.progress'))->streamedContent());
        $this->assertCount(1, $rows);
    }

    public function test_csv_escaping_and_formula_injection_protection(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->admin()->create();
        $course = $this->course('alpha', 'Alpha');
        // A hostile skill label exercising formula prefixes, commas,
        // quotes, newline, and Unicode in one cell.
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);
        Skill::query()->where('key', 'html.headings')->update([
            'label' => "=HYPERLINK(\"x\"), quoted\nSecond — ünïcödé @x",
        ]);
        // A hostile mission title exercising the same through a
        // challenge-owned dataset.
        $hostile = $this->mission($course, '@SUM(A1:A9), "evil"');
        $this->complete($student, $mission);
        $this->complete($student, $hostile);

        $body = $this->actingAs($student)->get(route('export.progress', ['dataset' => 'skills']))->streamedContent();

        // UTF-8 BOM opens the file.
        $this->assertSame("\xEF\xBB\xBF", substr($body, 0, 3));

        // The formula prefix is neutralized in the raw bytes.
        $this->assertStringContainsString("'=HYPERLINK", $body);

        // Round-trip: the parsed cell carries the neutralized value with
        // its injection-guard prefix and all hostile content intact.
        $rows = $this->parseCsv($body);
        $this->assertSame("'=HYPERLINK(\"x\"), quoted\nSecond — ünïcödé @x", $rows[1][1]);

        // Mission titles take the same guarded path in challenge datasets.
        $challenges = $this->actingAs($admin)->get(route('export.admin-system', ['dataset' => 'challenges']))->streamedContent();
        $this->assertStringContainsString("'@SUM", $challenges);
    }

    public function test_exporter_cell_formatting_rules(): void
    {
        $this->assertSame('', ReportCsvExporter::cell(null));
        $this->assertSame('true', ReportCsvExporter::cell(true));
        $this->assertSame('false', ReportCsvExporter::cell(false));
        $this->assertSame('0', ReportCsvExporter::cell(0));
        $this->assertSame('42', ReportCsvExporter::cell(42));
        $this->assertSame('33.33', ReportCsvExporter::cell(33.33));
        $this->assertSame('plain title', ReportCsvExporter::cell('plain title'));
        $this->assertSame("'=1+1", ReportCsvExporter::cell('=1+1'));
        $this->assertSame("'+1+1", ReportCsvExporter::cell('+1+1'));
        $this->assertSame("'-1+1", ReportCsvExporter::cell('-1+1'));
        $this->assertSame("'@user", ReportCsvExporter::cell('@user'));
        $this->assertSame("a,b\"c\nd", ReportCsvExporter::cell("a,b\"c\nd"));
    }

    public function test_exporter_adds_zero_database_queries(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);
        $this->complete($student, $mission);

        $report = app(StudentProgressReportService::class)->forStudent($student, null, null);
        $exporter = app(ReportCsvExporter::class);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $exporter->courses($report['progress']['courses']);
        $exporter->skills($report['competency']['skills']);
        $exporter->metrics($report['period']);
        $count = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        $this->assertSame(0, $count);
    }

    public function test_missing_teacher_subject_returns_not_found(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('export.teacher-student', 999999))->assertNotFound();
        $this->actingAs($teacher)->get(route('export.teacher-course', 999999))->assertNotFound();
    }
}
