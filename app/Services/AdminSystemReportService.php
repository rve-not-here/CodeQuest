<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use App\Support\ReportFilters;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Admin system report (US-1008). A descriptive, system-wide composition over
 * authoritative data. Nothing here invents metrics: every section is either a
 * verbatim subsection of an accepted reporting/domain service or a simple
 * COUNT of authoritative rows using the same predicates the owning service
 * uses. There are no rankings, health scores, predictions, recommendations,
 * grading formulas, or progression formulas anywhere below.
 *
 * Reporting contract:
 * - authorization: none lives here. The caller must already have established
 *   admin access through ReportAuthorizationService::canViewSystemReport. A
 *   null course scope means fleet-wide because the caller said so, never
 *   because null authorizes anything.
 * - users: fleet account facts (total/active/inactive/by role), current
 *   state. Same predicates as AdminDashboardService::metrics() and the
 *   AdminAnalyticsService role breakdown. The output carries an explicit
 *   fleet scope marker because no user filter narrows it.
 * - catalog: content facts (courses/sections/challenges/boss challenges).
 *   Current state; narrowed to the effective course scope when one applies.
 * - learning: course completions (sum of the completed buckets of
 *   CourseAnalyticsService::overview() rows, the exact operation
 *   AdminDashboardService uses), completed challenges (the404_progress row
 *   count, the AdminAnalyticsService predicate), active students
 *   (TeacherDashboardService::countActiveStudents, the authoritative
 *   four-source UNION definition over its own fixed activity window —
 *   never the selected report range; the window length travels in the
 *   output so the two time concepts cannot be confused). Current state.
 * - challenges: ChallengeAnalyticsService::summarize() verbatim (US-1006).
 * - assessments: AssessmentAnalyticsService::summarize() verbatim (US-1005).
 * - gamification: XpService::fleetSummary() verbatim, fleet all-time.
 *   XpService owns no student-scoped summary, so no student filter narrows
 *   it and none is invented here; the output carries an explicit fleet
 *   scope marker so a validated student_id can never look like it applies
 *   to these figures.
 * - period: descriptive movement inside the filter range (or all time when
 *   unfiltered): mission completions, wrong submissions, assessment
 *   attempts and passes. Each count reads its own authoritative table on
 *   that table's event timestamp and never feeds back into state.
 * - filter applicability: date narrows challenges/assessments/period only;
 *   student narrows learning/challenges/assessments/period only; course
 *   narrows catalog/learning/challenges/assessments/period only. Sections a
 *   dimension cannot move are labeled current-state or fleet facts in the
 *   contract above — no accepted dimension is silently ignored, and every
 *   scope mismatch fails closed to an empty scope. No status vocabulary is
 *   registered, so validated filters can never carry one.
 * - null/zero: empty scopes yield empty lists and zero counts; rates read
 *   null exactly when the owning service says so.
 */
class AdminSystemReportService
{
    public function __construct(
        private readonly AssessmentAnalyticsService $assessments,
        private readonly ChallengeAnalyticsService $challenges,
        private readonly CourseAnalyticsService $courses,
        private readonly TeacherDashboardService $teacher,
        private readonly XpService $xp,
    ) {}

    /**
     * @param  Collection<int, int>|null  $courseIds  pre-authorized scope; null is fleet-wide
     * @return array{
     *     users: array{scope: string, total: int, active: int, inactive: int, by_role: array<string, int>},
     *     catalog: array{courses: int, sections: int, challenges: int, boss_challenges: int},
     *     learning: array{course_completions: int, completed_challenges: int, active_students: int, active_students_window_days: int},
     *     challenges: array<string, mixed>,
     *     assessments: array<string, mixed>,
     *     gamification: array<string, mixed>,
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
    public function forSystem(?Collection $courseIds = null, ?ReportFilters $filters = null): array
    {
        $studentIds = $filters?->studentId !== null ? collect([$filters->studentId]) : null;
        $scope = $this->effectiveScope($courseIds, $filters);

        $courseRows = $this->courses->overview($studentIds, $scope);

        return [
            'users' => $this->users(),
            'catalog' => $this->catalog($scope),
            'learning' => $this->learning($studentIds, $scope, $courseRows),
            'challenges' => $this->challenges->summarize($studentIds, $scope, $filters),
            'assessments' => $this->assessments->summarize($studentIds, $scope, $filters),
            'gamification' => ['scope' => 'fleet'] + $this->xp->fleetSummary(),
            'period' => $this->periodActivity($studentIds, $scope, $filters),
        ];
    }

    /**
     * Intersect the caller's authorized course scope with the validated
     * filter course. null stays fleet-wide; a requested course outside the
     * authorized scope collapses to an empty scope so sections fail closed.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, int>|null
     */
    private function effectiveScope(?Collection $courseIds, ?ReportFilters $filters): ?Collection
    {
        if ($filters !== null && $filters->courseId !== null) {
            if ($courseIds !== null && ! $courseIds->contains($filters->courseId)) {
                return collect();
            }

            return collect([$filters->courseId]);
        }

        return $courseIds;
    }

    /**
     * @return array{scope: string, total: int, active: int, inactive: int, by_role: array<string, int>}
     */
    private function users(): array
    {
        $byRole = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $roles = [];

        foreach (UserService::ROLES as $role) {
            $roles[$role] = (int) ($byRole[$role] ?? 0);
        }

        return [
            'scope' => 'fleet',
            'total' => User::query()->count(),
            'active' => User::query()->where('status', 'active')->count(),
            'inactive' => User::query()->where('status', 'inactive')->count(),
            'by_role' => $roles,
        ];
    }

    /**
     * @param  Collection<int, int>|null  $scope
     * @return array{courses: int, sections: int, challenges: int, boss_challenges: int}
     */
    private function catalog(?Collection $scope): array
    {
        return [
            'courses' => $scope === null
                ? Course::query()->count()
                : Course::query()->whereIn('id', $scope)->count(),
            'sections' => $this->scopedCatalogCount(Section::query()->toBase(), $scope),
            'challenges' => $this->scopedCatalogCount(Mission::query()->toBase(), $scope),
            'boss_challenges' => $this->scopedCatalogCount(Assessment::query()->toBase(), $scope),
        ];
    }

    /**
     * @param  Builder  $query
     * @param  Collection<int, int>|null  $scope
     */
    private function scopedCatalogCount($query, ?Collection $scope): int
    {
        if ($scope !== null) {
            $query->whereIn('course_id', $scope);
        }

        return (int) $query->count();
    }

    /**
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $scope
     * @param  Collection<int, array{
     *     course: Course,
     *     fleet: int,
     *     engaged: int,
     *     buckets: array{completed: int, in_progress: int, assessment_ready: int, not_started: int},
     *     avg_completion: int|null,
     *     pass_rate: int|null,
     *     distribution: array<int, int>,
     * }>  $courseRows
     * @return array{course_completions: int, completed_challenges: int, active_students: int, active_students_window_days: int}
     */
    private function learning(?Collection $studentIds, ?Collection $scope, Collection $courseRows): array
    {
        $completions = DB::table('the404_progress');

        if ($scope !== null) {
            $completions->whereIn('mission_id', function ($query) use ($scope): void {
                $query->select('id')->from('the404_missions')->whereIn('course_id', $scope);
            });
        }

        if ($studentIds !== null) {
            $completions->whereIn('user_id', $studentIds);
        }

        return [
            'course_completions' => (int) $courseRows->sum(fn (array $row): int => $row['buckets']['completed']),
            'completed_challenges' => (int) $completions->count(),
            'active_students' => $this->teacher->countActiveStudents($studentIds),
            'active_students_window_days' => TeacherDashboardService::ACTIVE_WINDOW_DAYS,
        ];
    }

    /**
     * Descriptive movement inside the filter range. Each count reads its own
     * authoritative table; none feeds back into state computation.
     *
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $scope
     * @return array{
     *     from: string|null,
     *     to_exclusive: string|null,
     *     completions: int,
     *     wrong_submissions: int,
     *     assessment_attempts: int,
     *     assessment_passes: int,
     * }
     */
    private function periodActivity(?Collection $studentIds, ?Collection $scope, ?ReportFilters $filters): array
    {
        $completions = DB::table('the404_progress');

        if ($scope !== null) {
            $completions->whereIn('mission_id', function ($query) use ($scope): void {
                $query->select('id')->from('the404_missions')->whereIn('course_id', $scope);
            });
        }

        if ($studentIds !== null) {
            $completions->whereIn('user_id', $studentIds);
        }

        if ($filters !== null) {
            $filters->applyDateRange($completions, 'completed_at');
        }

        $wrong = DB::table('the404_xp_transactions')
            ->where('type', XpService::TYPE_WRONG_SUBMISSION);

        if ($scope !== null) {
            $wrong->whereIn('mission_id', function ($query) use ($scope): void {
                $query->select('id')->from('the404_missions')->whereIn('course_id', $scope);
            });
        }

        if ($studentIds !== null) {
            $wrong->whereIn('user_id', $studentIds);
        }

        if ($filters !== null) {
            $filters->applyDateRange($wrong, 'created_at');
        }

        $assessments = DB::table('the404_assessment_attempts as a')
            ->join('the404_assessments as s', 's.id', '=', 'a.assessment_id')
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw("SUM(CASE WHEN a.status = 'passed' THEN 1 ELSE 0 END) as passes");

        if ($scope !== null) {
            $assessments->whereIn('s.course_id', $scope);
        }

        if ($studentIds !== null) {
            $assessments->whereIn('a.user_id', $studentIds);
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
