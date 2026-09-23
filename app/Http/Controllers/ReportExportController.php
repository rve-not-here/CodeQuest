<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Services\AdminSystemReportService;
use App\Services\ReportAuthorizationService;
use App\Services\ReportCsvExporter;
use App\Services\ReportPdfExporter;
use App\Services\StudentProgressReportService;
use App\Services\TeacherCourseReportService;
use App\Services\TeacherStudentReportService;
use App\Support\ReportFilters;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phase 10 report exports (US-1010, CSV only). Thin download endpoints over
 * the accepted report services. Every action follows the same flow: validate
 * the export envelope, authorize through ReportAuthorizationService BEFORE
 * composing anything, derive the authorized scope, build validated
 * ReportFilters, invoke the accepted report service, and serialize only the
 * resulting authorized data. Controllers calculate nothing, read no domain
 * tables, and decide no scoping, readiness, or competency rules.
 *
 * PDF is served through the hardened ReportPdfExporter over the exact
 * same authorized report arrays. Unknown formats still fail validation.
 */
class ReportExportController extends Controller
{
    private const CSV_MIME = 'text/csv; charset=UTF-8';

    private const PDF_MIME = 'application/pdf';

    public function __construct(
        private readonly ReportAuthorizationService $authorization,
        private readonly ReportCsvExporter $csv,
        private readonly ReportPdfExporter $pdf,
        private readonly StudentProgressReportService $studentReport,
        private readonly TeacherStudentReportService $teacherStudentReport,
        private readonly TeacherCourseReportService $teacherCourseReport,
        private readonly AdminSystemReportService $adminReport,
    ) {}

    /**
     * @param  list<string>  $datasets
     * @return array{format: string, dataset: string}
     */
    private function exportEnvelope(Request $request, array $datasets): array
    {
        /** @var array{format?: string, dataset?: string} $validated */
        $validated = $request->validate([
            'format' => ['nullable', 'string', 'in:csv,pdf'],
            'dataset' => ['nullable', 'string', 'in:'.implode(',', $datasets)],
        ]);

        return [
            'format' => $validated['format'] ?? 'csv',
            'dataset' => $validated['dataset'] ?? $datasets[0],
        ];
    }

    private function filters(Request $request): ReportFilters
    {
        return ReportFilters::fromArray($request->only(['from', 'to', 'student_id', 'course_id', 'status']), []);
    }

    private function download(string $csv, string $filename): StreamedResponse
    {
        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename,
            ['Content-Type' => self::CSV_MIME],
        );
    }

    private function downloadPdf(string $pdf, string $filename): StreamedResponse
    {
        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf;
            },
            $filename,
            ['Content-Type' => self::PDF_MIME],
        );
    }

    /**
     * The student's own progress export. The subject is always the
     * authenticated viewer; a requested student id must match or the
     * composed report fails closed.
     */
    public function studentProgress(Request $request): StreamedResponse
    {
        ['format' => $format, 'dataset' => $dataset] = $this->exportEnvelope($request, ['courses', 'skills', 'period']);

        /** @var User $viewer */
        $viewer = $request->user();

        if (! $this->authorization->canViewStudentReport($viewer, $viewer)) {
            abort(403);
        }

        $report = $this->studentReport->forStudent(
            $viewer,
            $this->authorization->reportCourseIds($viewer, $viewer),
            $this->filters($request),
        );

        if ($format === 'pdf') {
            return $this->downloadPdf(
                $this->pdf->pdf($this->pdf->render($this->pdf->studentProgressView($report))),
                "student-progress-{$viewer->id}.pdf",
            );
        }

        $csv = match ($dataset) {
            'skills' => $this->csv->skills($report['competency']['skills']),
            'period' => $this->csv->metrics($report['period']),
            default => $this->csv->courses($report['progress']['courses']),
        };

        return $this->download($csv, "student-progress-{$viewer->id}.csv");
    }

    /**
     * Teacher's export for one authorized student. Only the teacher-visible
     * subset ever serializes: progress courses, competency skills, and
     * period movement. XP, achievements, and timeline have no dataset here.
     */
    public function teacherStudent(Request $request, User $student): StreamedResponse
    {
        ['format' => $format, 'dataset' => $dataset] = $this->exportEnvelope($request, ['courses', 'skills', 'period']);

        if ($student->role !== 'student') {
            abort(404);
        }

        try {
            /** @var User $viewer */
            $viewer = $request->user();

            if (! $this->authorization->canViewStudentReport($viewer, $student)) {
                abort(403);
            }

            $report = $this->teacherStudentReport->forTeacherStudent(
                $viewer,
                $student,
                $this->filters($request),
            );
        } catch (AuthorizationException) {
            abort(403);
        }

        if ($format === 'pdf') {
            return $this->downloadPdf(
                $this->pdf->pdf($this->pdf->render($this->pdf->teacherStudentView($report))),
                "teacher-student-{$student->id}.pdf",
            );
        }

        $csv = match ($dataset) {
            'skills' => $this->csv->skills($report['competency']['skills']),
            'period' => $this->csv->metrics($report['period']),
            default => $this->csv->courses($report['progress']['courses']),
        };

        return $this->download($csv, "teacher-student-{$student->id}.csv");
    }

    /**
     * Teacher's export for one authorized course: lifecycle aggregates,
     * per-student states, and period movement.
     */
    public function teacherCourse(Request $request, Course $course): StreamedResponse
    {
        ['format' => $format, 'dataset' => $dataset] = $this->exportEnvelope($request, ['lifecycle', 'students', 'period']);

        try {
            /** @var User $viewer */
            $viewer = $request->user();

            if (! $this->authorization->canViewCourseReport($viewer, $course)) {
                abort(403);
            }

            $report = $this->teacherCourseReport->forTeacherCourse(
                $viewer,
                $course,
                $this->filters($request),
            );
        } catch (AuthorizationException) {
            abort(403);
        }

        if ($format === 'pdf') {
            return $this->downloadPdf(
                $this->pdf->pdf($this->pdf->render($this->pdf->teacherCourseView($report))),
                "teacher-course-{$course->id}.pdf",
            );
        }

        $csv = match ($dataset) {
            'students' => $this->csv->students($report['competency']['students']),
            'period' => $this->csv->metrics($report['period']),
            default => $this->csv->metrics($report['lifecycle']),
        };

        return $this->download($csv, "teacher-course-{$course->id}.csv");
    }

    /**
     * Admin system export. Fleet/current/fixed-window/period labeling rides
     * along from the composed report contract; the summary dataset flattens
     * only scalar facts into deterministic metric rows.
     */
    public function adminSystem(Request $request): StreamedResponse
    {
        ['format' => $format, 'dataset' => $dataset] = $this->exportEnvelope($request, ['summary', 'period', 'challenges', 'assessments']);

        /** @var User $viewer */
        $viewer = $request->user();

        if (! $this->authorization->canViewSystemReport($viewer)) {
            abort(403);
        }

        $report = $this->adminReport->forSystem(null, $this->filters($request));

        if ($format === 'pdf') {
            return $this->downloadPdf(
                $this->pdf->pdf($this->pdf->render($this->pdf->adminSystemView($report))),
                'admin-system.pdf',
            );
        }

        $csv = match ($dataset) {
            'period' => $this->csv->metrics($report['period']),
            'challenges' => $this->csv->challenges($report['challenges']['by_challenge']),
            'assessments' => $this->csv->assessments($report['assessments']['by_assessment']),
            default => $this->csv->metrics($this->summaryMetrics($report)),
        };

        return $this->download($csv, 'admin-system.csv');
    }

    /**
     * Deterministic scalar flattening of the admin report's single-row
     * sections. Nested lists stay in their own datasets; scope and window
     * labels travel as explicit rows so fleet facts cannot read as
     * period-filtered ones.
     *
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function summaryMetrics(array $report): array
    {
        /** @var array<string, mixed> $users */
        $users = $report['users'];
        /** @var array<string, mixed> $catalog */
        $catalog = $report['catalog'];
        /** @var array<string, mixed> $learning */
        $learning = $report['learning'];
        /** @var array<string, mixed> $gamification */
        $gamification = $report['gamification'];
        /** @var array<string, int> $byRole */
        $byRole = $users['by_role'];

        $metrics = [
            'users.scope' => $users['scope'],
            'users.total' => $users['total'],
            'users.active' => $users['active'],
            'users.inactive' => $users['inactive'],
        ];

        foreach ($byRole as $role => $count) {
            $metrics["users.role.{$role}"] = $count;
        }

        return [...$metrics, ...[
            'catalog.courses' => $catalog['courses'],
            'catalog.sections' => $catalog['sections'],
            'catalog.challenges' => $catalog['challenges'],
            'catalog.boss_challenges' => $catalog['boss_challenges'],
            'learning.course_completions' => $learning['course_completions'],
            'learning.completed_challenges' => $learning['completed_challenges'],
            'learning.active_students' => $learning['active_students'],
            'learning.active_students_window_days' => $learning['active_students_window_days'],
            'gamification.scope' => $gamification['scope'],
            'gamification.awarded' => $gamification['awarded'],
            'gamification.spent' => $gamification['spent'],
            'gamification.deducted' => $gamification['deducted'],
            'gamification.outstanding' => $gamification['outstanding'],
            'gamification.accounts' => $gamification['accounts'],
        ]];
    }
}
