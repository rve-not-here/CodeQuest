<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Support\ReportFilters;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Teacher course report (US-1004). The teacher-facing aggregate report for
 * one authorized course, confined to the teacher's active classroom
 * relationships. Every lifecycle figure comes from the authoritative course
 * analytics buckets; every rate comes from its owning analytics service;
 * every competency row comes from the competency service. No progression,
 * readiness, completion, grading, ranking, risk, or scoring rule lives here.
 *
 * Authoritative definitions reused verbatim:
 * - participating: the course fleet — students the teacher may monitor for
 *   THIS course through active classrooms (ClassroomAccessService pairs),
 *   never all system students and never another course's population.
 * - not started: fleet minus engaged (students with no progress rows).
 * - in progress: engaged students who neither passed nor stand ready.
 * - assessment ready: the real Boss Challenge gate — assessment active,
 *   every mission complete, never passed (mirrors
 *   AssessmentService::isUnlocked/isEligible exactly).
 * - assessment attempted/passed: stored attempt verdicts at student grain
 *   (has_terminal/has_passed), never recomputed from live thresholds.
 * - course completed: a stored passed verdict (the US-410 history rule a
 *   later failed retry cannot undo).
 * The four buckets are exclusive and sum to the participating population.
 *
 * Authorization contract:
 * - ReportAuthorizationService::canViewCourseReport() runs first; anything
 *   else throws AuthorizationException before any data is composed.
 * - the student population derives only from the access pairs for this
 *   course; null pairs (fleet-wide callers) are refused on this teacher
 *   path rather than honored as fleet access.
 * - no classroom, role, or enrollment logic lives here.
 *
 * Filter contract:
 * - the requested course must equal the authorized course or the whole
 *   report fails closed to neutral; the course is never switched.
 * - a requested student must belong to the authorized population or the
 *   population collapses to empty; it can only narrow, never broaden.
 * - date narrows challenges/assessments/period only. Lifecycle buckets and
 *   competency stay cumulative/current by owner design.
 * - no status vocabulary is registered, so validated filters can never
 *   carry one. Filters can only narrow authorization, never broaden it.
 */
class TeacherCourseReportService
{
    public function __construct(
        private readonly ReportAuthorizationService $authorization,
        private readonly ClassroomAccessService $access,
        private readonly CourseAnalyticsService $courses,
        private readonly ChallengeAnalyticsService $challenges,
        private readonly AssessmentAnalyticsService $assessments,
        private readonly CompetencyService $competencies,
    ) {}

    /**
     * @return array{
     *     teacher_id: int,
     *     course: array{course_id: int, name: string, status: string},
     *     lifecycle: array{
     *         participating: int,
     *         engaged: int,
     *         not_started: int,
     *         in_progress: int,
     *         assessment_ready: int,
     *         completed: int,
     *         average_completion: int|null,
     *         pass_rate: int|null,
     *     },
     *     challenges: array<string, mixed>,
     *     assessments: array<string, mixed>,
     *     competency: array{
     *         students: list<array{student_id: int, state: string, percent: int}>,
     *         by_state: array<string, int>,
     *     },
     *     period: array{
     *         from: string|null,
     *         to_exclusive: string|null,
     *         completions: int,
     *         wrong_submissions: int,
     *         assessment_attempts: int,
     *         assessment_passes: int,
     *     },
     * }
     *
     * @throws AuthorizationException
     */
    public function forTeacherCourse(
        User $teacher,
        Course $course,
        ?ReportFilters $filters = null,
    ): array {
        if (! $this->authorization->canViewCourseReport($teacher, $course)) {
            throw new AuthorizationException('Not authorized to view this course report.');
        }

        if ($filters !== null && $filters->courseId !== null && $filters->courseId !== $course->id) {
            return $this->emptyReport($teacher, $course, $filters);
        }

        $population = $this->populationFor($teacher, $course);

        if ($filters !== null && $filters->studentId !== null) {
            $population = $population->contains($filters->studentId)
                ? collect([$filters->studentId])
                : collect();
        }

        $scope = collect([$course->id]);

        $rows = $this->courses->overview($population, $scope);
        $lifecycle = $rows->firstWhere('course.id', $course->id);

        $buckets = $lifecycle !== null
            ? $lifecycle['buckets']
            : ['completed' => 0, 'in_progress' => 0, 'assessment_ready' => 0, 'not_started' => 0];

        return [
            'teacher_id' => $teacher->id,
            'course' => [
                'course_id' => $course->id,
                'name' => $course->name,
                'status' => $course->status,
            ],
            'lifecycle' => [
                'participating' => $lifecycle['fleet'] ?? 0,
                'engaged' => $lifecycle['engaged'] ?? 0,
                'not_started' => $buckets['not_started'],
                'in_progress' => $buckets['in_progress'],
                'assessment_ready' => $buckets['assessment_ready'],
                'completed' => $buckets['completed'],
                'average_completion' => $lifecycle['avg_completion'] ?? null,
                'pass_rate' => $lifecycle['pass_rate'] ?? null,
            ],
            'challenges' => $this->challenges->summarize($population, $scope, $filters),
            'assessments' => $this->assessments->summarize($population, $scope, $filters),
            'competency' => $this->competencySummary($population, $scope),
            'period' => $this->periodActivity($population, $scope, $filters),
        ];
    }

    /**
     * Students the teacher may monitor for this course: the access pairs
     * filtered to the course id. Null pairs mean a fleet-wide caller and are
     * refused on this teacher path.
     *
     * @return Collection<int, int>
     *
     * @throws AuthorizationException
     */
    private function populationFor(User $teacher, Course $course): Collection
    {
        $byStudent = $this->access->scopesFor($teacher)['byStudent'];

        if ($byStudent === null) {
            throw new AuthorizationException('Not authorized to view this course report.');
        }

        $population = [];

        foreach ($byStudent as $studentId => $courseIds) {
            if ($courseIds->contains($course->id)) {
                $population[] = (int) $studentId;
            }
        }

        return collect($population)->sort()->values();
    }

    /**
     * Per-student course state from the single competency calculation path,
     * plus counts by state. Counting authoritative states is descriptive;
     * no class score, average, or threshold is computed here. Students are
     * resolved through the batch competency operation in bounded queries —
     * never one competency call per student.
     *
     * @param  Collection<int, int>  $population
     * @param  Collection<int, int>  $scope
     * @return array{
     *     students: list<array{student_id: int, state: string, percent: int}>,
     *     by_state: array<string, int>,
     * }
     */
    private function competencySummary(Collection $population, Collection $scope): array
    {
        $students = [];
        $byState = [];

        if ($population->isEmpty()) {
            return ['students' => $students, 'by_state' => $byState];
        }

        $users = User::query()->whereIn('id', $population)->get();
        $courseId = $scope->first();
        $rowsByUser = $this->competencies->overviewForStudents($users, $scope);

        foreach ($population as $studentId) {
            $row = $rowsByUser->get($studentId, collect())->firstWhere('course.id', $courseId);

            if ($row === null) {
                continue;
            }

            $students[] = [
                'student_id' => $studentId,
                'state' => $row['state'],
                'percent' => $row['percent'],
            ];
            $byState[$row['state']] = ($byState[$row['state']] ?? 0) + 1;
        }

        return [
            'students' => $students,
            'by_state' => $byState,
        ];
    }

    /**
     * Descriptive movement inside the filter range for the authorized
     * population. Each count reads its own authoritative table; none feeds
     * back into state computation.
     *
     * @param  Collection<int, int>  $population
     * @param  Collection<int, int>  $scope
     * @return array{
     *     from: string|null,
     *     to_exclusive: string|null,
     *     completions: int,
     *     wrong_submissions: int,
     *     assessment_attempts: int,
     *     assessment_passes: int,
     * }
     */
    private function periodActivity(Collection $population, Collection $scope, ?ReportFilters $filters): array
    {
        $missionIds = DB::table('the404_missions')->whereIn('course_id', $scope)->pluck('id');

        $completions = DB::table('the404_progress')
            ->whereIn('user_id', $population)
            ->whereIn('mission_id', $missionIds);

        if ($filters !== null) {
            $filters->applyDateRange($completions, 'completed_at');
        }

        $wrong = DB::table('the404_xp_transactions')
            ->whereIn('user_id', $population)
            ->where('type', XpService::TYPE_WRONG_SUBMISSION)
            ->whereIn('mission_id', $missionIds);

        if ($filters !== null) {
            $filters->applyDateRange($wrong, 'created_at');
        }

        $assessments = DB::table('the404_assessment_attempts as a')
            ->join('the404_assessments as s', 's.id', '=', 'a.assessment_id')
            ->whereIn('a.user_id', $population)
            ->whereIn('s.course_id', $scope)
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw("SUM(CASE WHEN a.status = 'passed' THEN 1 ELSE 0 END) as passes");

        if ($filters !== null) {
            $filters->applyDateRange($assessments, 'a.created_at');
        }

        $assessmentRow = $assessments->first();

        return [
            'from' => $filters?->from?->toDateTimeString(),
            'to_exclusive' => $filters?->toExclusive?->toDateTimeString(),
            'completions' => (int) $completions->count(),
            'wrong_submissions' => (int) $wrong->count(),
            'assessment_attempts' => (int) ($assessmentRow->attempts ?? 0),
            'assessment_passes' => (int) ($assessmentRow->passes ?? 0),
        ];
    }

    /**
     * Fail-closed report: the neutral shape carrying no population facts.
     *
     * @return array{
     *     teacher_id: int,
     *     course: array{course_id: int, name: string, status: string},
     *     lifecycle: array{
     *         participating: int,
     *         engaged: int,
     *         not_started: int,
     *         in_progress: int,
     *         assessment_ready: int,
     *         completed: int,
     *         average_completion: int|null,
     *         pass_rate: int|null,
     *     },
     *     challenges: array<string, mixed>,
     *     assessments: array<string, mixed>,
     *     competency: array{
     *         students: list<array{student_id: int, state: string, percent: int}>,
     *         by_state: array<string, int>,
     *     },
     *     period: array{
     *         from: string|null,
     *         to_exclusive: string|null,
     *         completions: int,
     *         wrong_submissions: int,
     *         assessment_attempts: int,
     *         assessment_passes: int,
     *     },
     * }
     */
    private function emptyReport(User $teacher, Course $course, ?ReportFilters $filters): array
    {
        $scope = collect([$course->id]);

        return [
            'teacher_id' => $teacher->id,
            'course' => [
                'course_id' => $course->id,
                'name' => $course->name,
                'status' => $course->status,
            ],
            'lifecycle' => [
                'participating' => 0,
                'engaged' => 0,
                'not_started' => 0,
                'in_progress' => 0,
                'assessment_ready' => 0,
                'completed' => 0,
                'average_completion' => null,
                'pass_rate' => null,
            ],
            'challenges' => $this->challenges->summarize(collect(), $scope, $filters),
            'assessments' => $this->assessments->summarize(collect(), $scope, $filters),
            'competency' => [
                'students' => [],
                'by_state' => [],
            ],
            'period' => [
                'from' => $filters?->from?->toDateTimeString(),
                'to_exclusive' => $filters?->toExclusive?->toDateTimeString(),
                'completions' => 0,
                'wrong_submissions' => 0,
                'assessment_attempts' => 0,
                'assessment_passes' => 0,
            ],
        ];
    }
}
