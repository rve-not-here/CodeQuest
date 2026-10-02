<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Services\AdminSystemReportService;
use App\Services\ClassroomAccessService;
use App\Services\ReportAuthorizationService;
use App\Services\ReportPdfExporter;
use App\Services\StudentProgressReportService;
use App\Services\StudentService;
use App\Services\TeacherCourseReportService;
use App\Services\TeacherStudentReportService;
use App\Support\ReportFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportAuthorizationService $authorization,
        private readonly ReportPdfExporter $presentation,
        private readonly StudentProgressReportService $students,
        private readonly TeacherStudentReportService $teacherStudents,
        private readonly TeacherCourseReportService $teacherCourses,
        private readonly AdminSystemReportService $system,
        private readonly StudentService $options,
        private readonly ClassroomAccessService $access,
    ) {}

    public function student(Request $request): View
    {
        /** @var User $viewer */
        $viewer = $request->user();
        abort_unless($this->authorization->canViewStudentReport($viewer, $viewer), 403);
        $report = $this->students->forStudent($viewer, $this->authorization->reportCourseIds($viewer, $viewer), $this->filters($request));

        return $this->page($request, $this->presentation->studentProgressView($report), 'export.progress', [], ['courses', 'skills', 'period']);
    }

    public function teacherIndex(): View
    {
        return view('reports.index');
    }

    public function teacherStudent(Request $request, User $student): View
    {
        /** @var User $viewer */
        $viewer = $request->user();
        abort_unless($student->role === 'student', 404);
        abort_unless($this->authorization->canViewStudentReport($viewer, $student), 403);
        $report = $this->teacherStudents->forTeacherStudent($viewer, $student, $this->filters($request));

        return $this->page($request, $this->presentation->teacherStudentView($report), 'export.teacher-student', ['student' => $student->id], ['courses', 'skills', 'period']);
    }

    public function teacherCourse(Request $request, Course $course): View
    {
        /** @var User $viewer */
        $viewer = $request->user();
        abort_unless($this->authorization->canViewCourseReport($viewer, $course), 403);
        $report = $this->teacherCourses->forTeacherCourse($viewer, $course, $this->filters($request));

        return $this->page($request, $this->presentation->teacherCourseView($report), 'export.teacher-course', ['course' => $course->id], ['lifecycle', 'students', 'period']);
    }

    public function system(Request $request): View
    {
        /** @var User $viewer */
        $viewer = $request->user();
        abort_unless($this->authorization->canViewSystemReport($viewer), 403);
        $report = $this->system->forSystem(null, $this->filters($request));

        return $this->page($request, $this->presentation->adminSystemView($report), 'export.admin-system', [], ['summary', 'period', 'challenges', 'assessments']);
    }

    private function filters(Request $request): ReportFilters
    {
        return ReportFilters::fromArray($request->only(['from', 'to', 'student_id', 'course_id', 'status']), []);
    }

    /**
     * @param  array{title: string, meta: list<array{label: string, value: string}>, sections: list<array{heading: string, note: string|null, columns: list<string>, rows: list<list<string>>}>}  $document
     * @param  array<string, int>  $exportParams
     * @param  list<string>  $datasets
     */
    private function page(Request $request, array $document, string $exportRoute, array $exportParams, array $datasets): View
    {
        /** @var User $viewer */
        $viewer = $request->user();

        return view('reports.show', [
            'document' => $document,
            'filters' => $request->only(['from', 'to', 'student_id', 'course_id']),
            'exportRoute' => $exportRoute,
            'exportParams' => $exportParams,
            'datasets' => $datasets,
            'courseOptions' => $this->options->courseOptions($viewer->role === 'student' ? null : $this->access->courseIdsFor($viewer)),
            'studentOptions' => in_array('students', $datasets, true) || in_array('summary', $datasets, true)
                ? $this->options->studentOptions($this->access->studentIdsFor($viewer)) : collect(),
        ]);
    }
}
