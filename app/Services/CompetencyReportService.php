<?php

namespace App\Services;

use App\Models\User;
use App\Support\ReportFilters;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Competency report (US-1007). Read-only descriptive repackaging of the
 * authoritative competency results. Nothing here calculates mastery,
 * weights evidence, classifies states, or touches progression; every
 * value below is projected verbatim from CompetencyService, which remains
 * the single calculation path.
 *
 * Reporting contract:
 * - population/grain: one student per call; one row per course and per
 *   skill within the applicable course scope.
 * - current state: course states and skill percentages as computed today
 *   from all recorded evidence. State is current, never backdated: the
 *   report describes what the evidence says now.
 * - historical evidence: period counts (completions, wrong submissions,
 *   assessment attempts and passes) describe activity inside the filter
 *   range only. They explain what moved, never recompute state.
 * - date/time: ReportFilters half-open range on each table's own event
 *   timestamp (completed_at for completions, created_at otherwise). With
 *   no range the period covers all time.
 * - course/student filtering: the caller's course scope and the validated
 *   ReportFilters student/course ids intersect, and every mismatch fails
 *   closed to an empty scope. A requested student other than the report
 *   student, or a requested course outside the authorized scope, yields
 *   empty lists and zero period counts — never another student's rows.
 *   No status vocabulary is registered for this report, so validated
 *   filters can never carry one.
 * - null/zero: empty scopes yield empty lists; counts read zero.
 * - authorization: none lives here. Callers scope courses through
 *   ReportAuthorizationService before calling.
 */
class CompetencyReportService
{
    public function __construct(
        private readonly CompetencyService $competencies,
    ) {}

    /**
     * @param  Collection<int, int>|null  $courseIds
     * @return array{
     *     student_id: int,
     *     courses: list<array{
     *         course_id: int,
     *         name: string,
     *         state: string,
     *         percent: int,
     *         completed_missions: int,
     *         total_missions: int,
     *         wrong_submissions: int,
     *         attempts: int,
     *         challenge_passed: bool,
     *     }>,
     *     skills: list<array{
     *         key: string,
     *         label: string,
     *         percentage: float|null,
     *         state: string,
     *         weak: bool,
     *         kc_correct: int,
     *         kc_total: int,
     *         challenges_completed: int,
     *         challenges_applicable: int,
     *     }>,
     *     weak_skills: list<string>,
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
    public function forStudent(
        User $student,
        ?Collection $courseIds = null,
        ?ReportFilters $filters = null,
    ): array {
        $scope = $this->effectiveScope($student, $courseIds, $filters);

        $courses = $this->competencies->overview($student, $scope);
        $skills = $this->competencies->skills($student, $scope);

        $courseRows = [];

        foreach ($courses as $row) {
            $courseRows[] = [
                'course_id' => $row['course']->id,
                'name' => $row['name'],
                'state' => $row['state'],
                'percent' => $row['percent'],
                'completed_missions' => $row['completedMissions'],
                'total_missions' => $row['totalMissions'],
                'wrong_submissions' => $row['wrongSubmissions'],
                'attempts' => $row['attempts'],
                'challenge_passed' => $row['challengePassed'],
            ];
        }

        $skillRows = [];

        foreach ($skills as $row) {
            $skillRows[] = [
                'key' => $row['key'],
                'label' => $row['label'],
                'percentage' => $row['percentage'],
                'state' => $row['state'],
                'weak' => $row['weak'],
                'kc_correct' => $row['kcCorrect'],
                'kc_total' => $row['kcTotal'],
                'challenges_completed' => $row['challengesCompleted'],
                'challenges_applicable' => $row['challengesApplicable'],
            ];
        }

        $weakSkills = [];

        foreach ($skills as $row) {
            if ($row['weak']) {
                $weakSkills[] = $row['key'];
            }
        }

        return [
            'student_id' => $student->id,
            'courses' => $courseRows,
            'skills' => $skillRows,
            'weak_skills' => $weakSkills,
            'period' => $this->periodActivity($student, $scope, $filters),
        ];
    }

    /**
     * Intersect the caller's authorized course scope with the validated
     * ReportFilters student/course ids. null stays fleet-wide; any
     * mismatch collapses to an empty scope so the report fails closed.
     * An empty Collection scope yields no rows downstream.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, int>|null
     */
    private function effectiveScope(User $student, ?Collection $courseIds, ?ReportFilters $filters): ?Collection
    {
        if ($filters !== null && $filters->studentId !== null && $filters->studentId !== $student->id) {
            return collect();
        }

        if ($filters !== null && $filters->courseId !== null) {
            if ($courseIds !== null && ! $courseIds->contains($filters->courseId)) {
                return collect();
            }

            return collect([$filters->courseId]);
        }

        return $courseIds;
    }

    /**
     * Descriptive activity counts inside the filter range (or all time when
     * unfiltered). Each count reads its own authoritative table; none of
     * them feeds back into state computation.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return array{
     *     from: string|null,
     *     to_exclusive: string|null,
     *     completions: int,
     *     wrong_submissions: int,
     *     assessment_attempts: int,
     *     assessment_passes: int,
     * }
     */
    private function periodActivity(User $student, ?Collection $courseIds, ?ReportFilters $filters): array
    {
        $missionIds = $courseIds === null
            ? null
            : DB::table('the404_missions')->whereIn('course_id', $courseIds)->pluck('id');

        $completions = DB::table('the404_progress')->where('user_id', $student->id);

        if ($missionIds !== null) {
            $completions->whereIn('mission_id', $missionIds);
        }

        if ($filters !== null) {
            $filters->applyDateRange($completions, 'completed_at');
        }

        $wrong = DB::table('the404_xp_transactions')
            ->where('user_id', $student->id)
            ->where('type', XpService::TYPE_WRONG_SUBMISSION);

        if ($missionIds !== null) {
            $wrong->whereIn('mission_id', $missionIds);
        }

        if ($filters !== null) {
            $filters->applyDateRange($wrong, 'created_at');
        }

        $assessments = DB::table('the404_assessment_attempts as a')
            ->join('the404_assessments as s', 's.id', '=', 'a.assessment_id')
            ->where('a.user_id', $student->id)
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw("SUM(CASE WHEN a.status = 'passed' THEN 1 ELSE 0 END) as passes");

        if ($courseIds !== null) {
            $assessments->whereIn('s.course_id', $courseIds);
        }

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
}
