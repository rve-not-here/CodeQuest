<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AssessmentService;
use App\Services\ClassroomAccessService;
use App\Services\CompetencyService;
use App\Services\CourseProgressService;
use App\Services\RecommendationService;
use App\Services\ResumeService;
use App\Services\SectionProgressService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StudentProgressController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CourseProgressService $progress,
        private readonly SectionProgressService $sections,
        private readonly ResumeService $resume,
        private readonly AssessmentService $assessments,
        private readonly CompetencyService $competencies,
        private readonly RecommendationService $recommendations,
        private readonly ClassroomAccessService $access,
    ) {}

    /**
     * The per-student progress detail (US-603), bound to one student via the
     * path and composed entirely from the Phase 5 services — CourseProgressService
     * (per-course state + mission counts), SectionProgressService (real
     * course->section->mission hierarchy), ResumeService (current learning
     * position). No hierarchy is re-derived here.
     *
     * The bound identifier is guarded by UserPolicy::view — the confirmed
     * classroom model (Classroom / Enrollment Authorization): an admin may
     * view any student; a teacher may view only students enrolled at one of
     * their ACTIVE classrooms, and the data itself is course-scoped to the
     * ACTIVE shared classrooms (teacher assigned + student enrolled + course
     * assigned), so a teacher sharing one course with the student never sees
     * that student's other classrooms' work. A missing student bounces to the
     * roster; a non-student target is 404.
     */
    public function __invoke(?User $student = null): RedirectResponse|View
    {
        if ($student === null) {
            return redirect()->route('students');
        }

        if ($student->role !== 'student') {
            abort(404);
        }

        $this->authorize('view', $student);

        /** @var User $teacher */
        $teacher = auth()->user();

        $courseIds = $this->access->courseIdsForStudent($teacher, $student);

        $position = $this->resume->resolve($student);
        $positionCourseId = $position !== null ? (int) $position['course']->id : null;

        if ($courseIds !== null && $positionCourseId !== null && ! $courseIds->contains($positionCourseId)) {
            $position = null;
            $positionCourseId = null;
        }

        $sectionsById = $this->sections->overview($student)
            ->keyBy(fn (array $row): int => $row['course']->id)
            ->filter(function (array $row, int $courseId) use ($courseIds): bool {
                return $courseIds === null || $courseIds->contains($courseId);
            });

        $rows = $this->progress->overview($student, $courseIds)
            ->map(function (array $row) use ($sectionsById, $positionCourseId): array {
                $course = $row['course'];
                $courseSections = $sectionsById->get($course->id);

                return [
                    'course' => $row['course'],
                    'progress' => $row['progress'],
                    'state' => $row['state'],
                    'sections' => $courseSections !== null ? $courseSections['sections'] : collect(),
                    'isPosition' => $course->id === $positionCourseId,
                ];
            });

        // US-604 assessment performance, per assessed course, composed from
        // AssessmentService::forCourse()/attemptHistory() and the page's own
        // course-state rows. Strictly read-only: nothing here calls a write
        // method, and the route accepts no input beyond the path-bound student.
        $performance = collect();

        foreach ($rows as $row) {
            $course = $row['course'];

            if ($this->assessments->forCourse($course) === null) {
                continue;
            }

            $history = $this->assessments->attemptHistory($student, $course);

            $performance->push([
                'course' => $course,
                'state' => $row['state'],
                'attemptCount' => $history->count(),
                'latest' => $history->first(),
                'completedAt' => $history
                    ->where('status', 'passed')
                    ->pluck('passed_at')
                    ->filter()
                    ->min(),
                'attempts' => $history
                    ->map(fn (array $attempt): array => $attempt + ['courseName' => $course->name])
                    ->values(),
            ]);
        }

        $attemptLog = $performance->flatMap(fn (array $row): Collection => $row['attempts']);

        // US-605 competency monitoring, the §26 ladder. Composed by the exact
        // same CompetencyService::overview() the student dashboard renders —
        // one source of truth, no teacher-specific formula (§15.0). Read-only.
        // Course-scoped to the teacher's shared classrooms like the rows above.
        $competency = $this->competencies->overview($student, $courseIds);

        // US-909 teacher recommendation visibility. The same
        // RecommendationService output the student sees, filtered to the
        // teacher's shared courses inside the service — no eligibility is
        // calculated here, and the view renders it without action links.
        $recommendations = $this->recommendations->recommendations($student, $courseIds);

        return view('student-progress', [
            'student' => $student,
            'role' => $teacher->role,
            'rows' => $rows,
            'position' => $position,
            'performance' => $performance,
            'attemptLog' => $attemptLog,
            'competency' => $competency,
            'recommendations' => $recommendations,
        ]);
    }
}
