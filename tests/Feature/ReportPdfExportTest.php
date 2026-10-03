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
use App\Services\ReportPdfExporter;
use App\Services\StudentProgressReportService;
use App\Services\TeacherCourseReportService;
use App\Services\TeacherStudentReportService;
use App\Support\ReportFilters;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\Concerns\WithClassroomScope;
use Tests\TestCase;

/**
 * US-1010 PDF exports. View-model parity against the authoritative report
 * arrays, rendered-HTML safety, hardened renderer options, and endpoint
 * authorization/filter behavior. PDF bytes are opaque by design, so content
 * assertions live at the view-model and HTML layers, never by re-deriving
 * report formulas.
 */
class ReportPdfExportTest extends TestCase
{
    use RefreshDatabase;
    use WithClassroomScope;

    private static int $orderSequence = 17000;

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

    private function filters(array $input = []): ReportFilters
    {
        return ReportFilters::fromArray($input, []);
    }

    private function exporter(): ReportPdfExporter
    {
        return app(ReportPdfExporter::class);
    }

    public function test_text_formatting_and_options_are_hardened(): void
    {
        $exporter = $this->exporter();

        $this->assertSame('—', ReportPdfExporter::text(null));
        $this->assertSame('Yes', ReportPdfExporter::text(true));
        $this->assertSame('No', ReportPdfExporter::text(false));
        $this->assertSame('0', ReportPdfExporter::text(0));
        $this->assertSame('42', ReportPdfExporter::text(42));
        $this->assertSame('33.33', ReportPdfExporter::text(33.33));
        $this->assertSame('plain', ReportPdfExporter::text('plain'));

        $this->assertSame(
            [
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isJavascriptEnabled' => false,
                'allowedRemoteHosts' => [],
            ],
            $exporter->options()
        );
        $this->assertSame('All time', $exporter->periodLabel(['from' => null, 'to_exclusive' => null]));
        $this->assertSame(
            '2026-05-01 00:00:00 to 2026-06-01 00:00:00',
            $exporter->periodLabel(['from' => '2026-05-01 00:00:00', 'to_exclusive' => '2026-06-01 00:00:00'])
        );
    }

    public function test_student_view_model_mirrors_report_with_temporal_labels(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);
        $this->assessment($course);
        $this->complete($student, $mission);
        $this->attemptAssessment($student, $course, 'SIGNAL answer');

        $report = app(StudentProgressReportService::class)->forStudent($student, null, null);
        $view = $this->exporter()->studentProgressView($report);

        $this->assertSame('Student Progress Report', $view['title']);
        $this->assertSame((string) $student->id, $this->metaValue($view, 'Student ID'));
        $this->assertSame('All time', $this->metaValue($view, 'Period'));

        $sections = collect($view['sections'])->keyBy('heading');
        $this->assertSame($report['progress']['courses'][0]['state'], $sections['Courses']['rows'][0][2]);
        $this->assertSame($report['competency']['skills'][0]['key'], $sections['Skills']['rows'][0][0]);
        $this->assertSame('Cumulative current state.', $sections['Courses']['note']);
        $this->assertSame('Cumulative current state.', $sections['Skills']['note']);
        $this->assertSame('Selected-period movement.', $sections['Period movement']['note']);

        $period = collect($sections['Period movement']['rows'])->mapWithKeys(fn (array $row): array => [$row[0] => $row[1]]);
        $this->assertSame((string) $report['period']['completions'], $period['completions']);
        $this->assertSame((string) $report['period']['assessment_passes'], $period['assessment_passes']);
    }

    public function test_teacher_student_view_model_has_scope_and_no_private_sections(): void
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

        $report = app(TeacherStudentReportService::class)->forTeacherStudent($teacher, $student, null);
        $view = $this->exporter()->teacherStudentView($report);

        $this->assertSame('Teacher Student Report', $view['title']);
        $this->assertSame((string) $courseA->id, $this->metaValue($view, 'Authorized courses'));

        $headings = collect($view['sections'])->pluck('heading')->all();
        $this->assertSame(['Courses', 'Skills', 'Challenges', 'Assessments', 'Recommendations', 'Period movement'], $headings);

        // No XP, achievement, streak, or timeline section exists in the model.
        $this->assertStringNotContainsStringIgnoringCase('balance', json_encode($view));
        $this->assertStringNotContainsStringIgnoringCase('streak', json_encode($view));
        $this->assertStringNotContainsStringIgnoringCase('timeline', json_encode($view));
    }

    public function test_teacher_course_view_model_mirrors_report(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->assessment($course);

        $this->classroomFor($teacher, [$student], [$course]);
        $this->complete($student, $mission);
        $this->attemptAssessment($student, $course, 'SIGNAL answer');

        $report = app(TeacherCourseReportService::class)->forTeacherCourse($teacher, $course, null);
        $view = $this->exporter()->teacherCourseView($report);

        $this->assertSame('Teacher Course Report', $view['title']);

        $sections = collect($view['sections'])->keyBy('heading');
        $lifecycle = collect($sections['Lifecycle']['rows'])->mapWithKeys(fn (array $row): array => [$row[0] => $row[1]]);
        $this->assertSame((string) $report['lifecycle']['participating'], $lifecycle['participating']);
        $this->assertSame((string) $report['lifecycle']['completed'], $lifecycle['completed']);
        $this->assertStringContainsString('mutually exclusive', (string) $sections['Lifecycle']['note']);

        $students = $sections['Competency by student']['rows'];
        $this->assertSame((string) $student->id, $students[0][0]);
        $this->assertSame('demonstrated', $students[0][1]);
    }

    public function test_admin_view_model_labels_fleet_and_window(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($student, $mission);

        $report = app(AdminSystemReportService::class)->forSystem(null, null);
        $view = $this->exporter()->adminSystemView($report);

        $this->assertSame('Admin System Report', $view['title']);
        $this->assertSame('Fleet-wide', $this->metaValue($view, 'Scope'));

        $sections = collect($view['sections'])->keyBy('heading');
        $gamification = collect($sections['Gamification']['rows'])->mapWithKeys(fn (array $row): array => [$row[0] => $row[1]]);
        $this->assertSame('fleet', $gamification['scope']);
        $this->assertSame((string) $report['gamification']['awarded'], $gamification['awarded']);
        $this->assertStringContainsString('all-time', (string) $sections['Gamification']['note']);

        $learning = collect($sections['Learning']['rows'])->mapWithKeys(fn (array $row): array => [$row[0] => $row[1]]);
        $this->assertSame((string) $report['learning']['active_students_window_days'], $learning['active_students_window_days']);
        $this->assertStringContainsString('independent of the selected period', (string) $sections['Learning']['note']);
    }

    public function test_mismatched_student_view_model_stays_neutral(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($student, $mission);

        $report = app(StudentProgressReportService::class)->forStudent(
            $student,
            null,
            $this->filters(['student_id' => $other->id]),
        );
        $view = $this->exporter()->studentProgressView($report);

        $sections = collect($view['sections'])->keyBy('heading');
        $this->assertSame([], $sections['Courses']['rows']);
        $this->assertSame([], $sections['Skills']['rows']);

        $period = collect($sections['Period movement']['rows'])->mapWithKeys(fn (array $row): array => [$row[0] => $row[1]]);
        $this->assertSame('0', $period['completions']);
    }

    public function test_date_filtered_view_model_narrows_period_only(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $april = $this->mission($course, 'April Mission');
        $may = $this->mission($course, 'May Mission');
        $this->complete($student, $april);
        $this->complete($student, $may);

        DB::table('the404_progress')->where('mission_id', $april->id)->update(['completed_at' => '2026-04-10 09:00:00']);
        DB::table('the404_progress')->where('mission_id', $may->id)->update(['completed_at' => '2026-05-10 09:00:00']);

        $report = app(StudentProgressReportService::class)->forStudent(
            $student,
            null,
            $this->filters(['from' => '2026-05-01', 'to' => '2026-05-31']),
        );
        $view = $this->exporter()->studentProgressView($report);

        $this->assertSame('2026-05-01 00:00:00 to 2026-06-01 00:00:00', $this->metaValue($view, 'Period'));

        $sections = collect($view['sections'])->keyBy('heading');

        // One course row, cumulative (2 of 2); only the period narrows.
        $this->assertCount(1, $sections['Courses']['rows']);
        $this->assertSame('2', $sections['Courses']['rows'][0][4]);

        $period = collect($sections['Period movement']['rows'])->mapWithKeys(fn (array $row): array => [$row[0] => $row[1]]);
        $this->assertSame('1', $period['completions']);
    }

    public function test_rendered_html_escapes_hostile_content(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha <script>alert(1)</script>', ['html.headings']);
        Skill::query()->where('key', 'html.headings')->update([
            'label' => '<img src="https://example.invalid/x"> ünïcödé',
        ]);
        $this->complete($student, $mission);

        // Skill labels flow into the student document; mission titles flow
        // into the admin challenge detail. Both must render as text.
        $report = app(StudentProgressReportService::class)->forStudent($student, null, null);
        $html = $this->exporter()->render($this->exporter()->studentProgressView($report));

        $this->assertStringContainsString('&lt;img src=', $html);
        $this->assertStringContainsString('ünïcödé', $html);
        $this->assertStringNotContainsString('<img src="https://example.invalid/x">', $html);

        $admin = app(AdminSystemReportService::class)->forSystem(null, null);
        $adminHtml = $this->exporter()->render($this->exporter()->adminSystemView($admin));

        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $adminHtml);
        $this->assertStringNotContainsString('<script>', $adminHtml);

        // The template itself references no remote or local assets.
        $this->assertStringNotContainsString('<img', $html.$adminHtml);
        $this->assertStringNotContainsString('<link', $html.$adminHtml);
        $this->assertStringNotContainsString('<script', $html.$adminHtml);
    }

    public function test_javascript_looking_content_renders_as_text_only(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, '<script type="text/javascript">app.alert(\'CodeQuest\')</script>');
        $this->complete($student, $mission);

        $report = app(AdminSystemReportService::class)->forSystem(null, null);
        $html = $this->exporter()->render($this->exporter()->adminSystemView($report));

        // The raw script element is absent; its text survives only escaped.
        $this->assertStringNotContainsString('<script type="text/javascript">', $html);
        $this->assertStringContainsString('&lt;script type=&quot;text/javascript&quot;&gt;', $html);
        $this->assertStringContainsString('app.alert(', $html);

        // Renderer JavaScript stays disabled regardless of content.
        $this->assertFalse($this->exporter()->options()['isJavascriptEnabled']);

        // The full pipeline still emits a valid PDF for hostile input.
        $pdf = $this->exporter()->pdf($html);
        $this->assertSame('%PDF-', substr($pdf, 0, 5));
    }

    public function test_pdf_bytes_render_without_database_access_or_leftovers(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One', ['html.headings']);
        $this->complete($student, $mission);

        $report = app(StudentProgressReportService::class)->forStudent($student, null, null);
        $view = $this->exporter()->studentProgressView($report);

        $before = $this->storageFiles();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $html = $this->exporter()->render($view);
        $pdf = $this->exporter()->pdf($html);
        $count = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        $this->assertSame(0, $count);
        $this->assertSame('%PDF-', substr($pdf, 0, 5));
        $this->assertSame($before, $this->storageFiles());
    }

    public function test_student_self_pdf_endpoint(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');
        $this->complete($student, $mission);

        $response = $this->actingAs($student)->get(route('export.progress', ['format' => 'pdf']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString("student-progress-{$student->id}.pdf", $response->headers->get('Content-Disposition'));
        $this->assertSame('%PDF-', substr($response->streamedContent(), 0, 5));
    }

    public function test_teacher_student_pdf_endpoint_and_denial(): void
    {
        $teacher = User::factory()->teacher()->create();
        $outsider = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');

        $this->classroomFor($teacher, [$student], [$course]);
        $this->complete($student, $mission);

        $response = $this->actingAs($teacher)->get(route('export.teacher-student', [$student, 'format' => 'pdf']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame('%PDF-', substr($response->streamedContent(), 0, 5));

        $this->actingAs($outsider)->get(route('export.teacher-student', [$student, 'format' => 'pdf']))->assertForbidden();
    }

    public function test_teacher_course_pdf_endpoint_and_denial(): void
    {
        $teacher = User::factory()->teacher()->create();
        $courseA = $this->course('ca', 'Course A');
        $courseB = $this->course('cb', 'Course B');
        $this->mission($courseA, 'Alpha One');
        $this->mission($courseB, 'Beta One');

        $this->classroomFor($teacher, [], [$courseA]);

        $this->actingAs($teacher)->get(route('export.teacher-course', [$courseA, 'format' => 'pdf']))->assertOk();
        $this->actingAs($teacher)->get(route('export.teacher-course', [$courseB, 'format' => 'pdf']))->assertForbidden();
    }

    public function test_admin_pdf_endpoint_and_denial(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($admin)->get(route('export.admin-system', ['format' => 'pdf']));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('admin-system.pdf', $response->headers->get('Content-Disposition'));
        $this->assertSame('%PDF-', substr($response->streamedContent(), 0, 5));

        $this->actingAs($teacher)->get(route('export.admin-system', ['format' => 'pdf']))->assertForbidden();
    }

    public function test_pdf_endpoints_share_filter_and_mismatch_behavior(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $other = User::factory()->create(['role' => 'student']);
        $course = $this->course('alpha', 'Alpha');
        $mission = $this->mission($course, 'Alpha One');

        $this->classroomFor($teacher, [$student, $other], [$course]);
        $this->complete($student, $mission);

        // Matching student filter preserves the download.
        $this->actingAs($teacher)
            ->get(route('export.teacher-student', [$student, 'format' => 'pdf', 'student_id' => $student->id]))
            ->assertOk();

        // Mismatched student fails the composed report closed, still a
        // valid PDF response carrying the neutral shape.
        $mismatched = $this->actingAs($teacher)
            ->get(route('export.teacher-student', [$student, 'format' => 'pdf', 'student_id' => $other->id]));
        $mismatched->assertOk();
        $this->assertSame('%PDF-', substr($mismatched->streamedContent(), 0, 5));

        // Invalid filters redirect exactly like the CSV path.
        $this->actingAs($teacher)
            ->get(route('export.teacher-student', [$student, 'format' => 'pdf', 'status' => 'active']))
            ->assertRedirect();
    }

    /**
     * @return list<string>
     */
    private function storageFiles(): array
    {
        $files = [];

        foreach (File::allFiles(storage_path('app')) as $file) {
            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    private function metaValue(array $view, string $label): ?string
    {
        foreach ($view['meta'] as $item) {
            if ($item['label'] === $label) {
                return $item['value'];
            }
        }

        return null;
    }
}
