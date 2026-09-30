<?php

namespace App\Services;

use App\Support\ReportFilters;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Boss Challenge assessment analytics (US-1005). Read-only descriptive
 * metrics over persisted attempt rows. Nothing here scores, grades, or
 * mutates; evaluation stays entirely inside AssessmentService.
 *
 * Metric definitions (one authoritative wording shared by UI, CSV, and PDF):
 *
 * - attempts: persisted the404_assessment_attempt rows in scope, every
 *   status. Population: rows. Time range: created_at inside the filter
 *   range. Retries included; no row is invented or inferred.
 * - passes: rows with status 'passed'. The stored verdict, never recomputed
 *   from the live passing score, so later threshold edits cannot rewrite it.
 * - failures: rows with status 'failed', same stored-verdict guarantee.
 * - average_score: rounded integer mean of recorded non-null scores, null
 *   when no scored attempt exists. Population: scored rows. In-flight rows
 *   (available/started/submitted) carry no score and are excluded.
 * - pass_rate: rounded integer percent of distinct (user, assessment) pairs
 *   with a passed row among pairs with a terminal (passed/failed) row, null
 *   when no terminal pair exists. Pairs, not bare users: one student passing
 *   one of two assessments reads 50, not 100. At a single-assessment scope
 *   this coincides exactly with the established CourseAnalyticsService
 *   shape (distinct passed students over distinct terminal students).
 * - retries: in-range attempt rows preceded by an earlier row for the same
 *   (user, assessment) pair anywhere in full history. Only retryAttempt()
 *   can create a non-first row (it refuses without a prior terminal
 *   outcome), so this counts actual retries. A first attempt that predates
 *   the selected range still marks its in-range successor as a retry, while
 *   only in-range rows contribute to counts.
 * - completion: distinct (user, assessment) pairs with at least one passed
 *   row in scope — the hasPassed() rule aggregated. One student passing two
 *   courses counts twice; retry passes never double-count.
 *
 * Status filter vocabulary: the six persisted attempt statuses from the
 * the404_assessment_attempts enum (available, started, submitted, evaluated,
 * passed, failed). A status slice narrows every metric to rows in that
 * state; it is validated by ReportFilters against STATUSES.
 *
 * Scopes follow the analytics convention: null student/course sets mean
 * unconstrained (admin fleet); ReportFilters narrow within the authorized
 * scope and never widen it. Authorization itself belongs to
 * ReportAuthorizationService and runs before this service is called.
 */
class AssessmentAnalyticsService
{
    /**
     * The persisted attempt-status vocabulary. Mirrors the
     * the404_assessment_attempts status enum exactly; report callers pass
     * this as the ReportFilters status vocabulary so only real states are
     * accepted. Statuses are never invented here.
     *
     * @var list<string>
     */
    public const STATUSES = ['available', 'started', 'submitted', 'evaluated', 'passed', 'failed'];

    /**
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $courseIds
     * @return array{
     *     attempts: int,
     *     passes: int,
     *     failures: int,
     *     average_score: int|null,
     *     pass_rate: int|null,
     *     retries: int,
     *     completion: int,
     *     by_assessment: list<array{
     *         assessment_id: int,
     *         course_id: int,
     *         title: string,
     *         attempts: int,
     *         passes: int,
     *         failures: int,
     *         average_score: int|null,
     *         pass_rate: int|null,
     *         retries: int,
     *         completion: int,
     *     }>,
     * }
     */
    public function summarize(
        ?Collection $studentIds = null,
        ?Collection $courseIds = null,
        ?ReportFilters $filters = null,
    ): array {
        [$studentIds, $courseIds] = $this->applyFilters($studentIds, $courseIds, $filters);

        $overall = $this->overallMetrics($studentIds, $courseIds, $filters);
        $byAssessment = $this->metricsByAssessment($studentIds, $courseIds, $filters);

        return [...$overall, 'by_assessment' => $byAssessment];
    }

    /**
     * Intersect authorization scopes with validated filter focus. A filter
     * id outside the authorized scope yields an empty set (fail closed),
     * never the scope itself.
     *
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $courseIds
     * @return array{Collection<int, int>|null, Collection<int, int>|null}
     */
    private function applyFilters(?Collection $studentIds, ?Collection $courseIds, ?ReportFilters $filters): array
    {
        if ($filters === null) {
            return [$studentIds, $courseIds];
        }

        if ($filters->studentId !== null) {
            $studentIds = $studentIds === null
                ? collect([$filters->studentId])
                : $studentIds->intersect([$filters->studentId])->values();
        }

        if ($filters->courseId !== null) {
            $courseIds = $courseIds === null
                ? collect([$filters->courseId])
                : $courseIds->intersect([$filters->courseId])->values();
        }

        return [$studentIds, $courseIds];
    }

    /**
     * The shared filtered attempt scope. Date range applies to created_at
     * uniformly; every metric reads the same population. An optional attempt
     * status narrows rows; it arrives already validated against STATUSES.
     *
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $courseIds
     */
    private function baseQuery(?Collection $studentIds, ?Collection $courseIds, ?ReportFilters $filters): Builder
    {
        $query = DB::table('the404_assessment_attempts as a')
            ->join('the404_assessments as s', 's.id', '=', 'a.assessment_id');

        if ($studentIds !== null) {
            $query->whereIn('a.user_id', $studentIds);
        }

        if ($courseIds !== null) {
            $query->whereIn('s.course_id', $courseIds);
        }

        if ($filters !== null) {
            $filters->applyDateRange($query, 'a.created_at');

            if ($filters->status !== null) {
                $query->where('a.status', $filters->status);
            }
        }

        return $query;
    }

    /**
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $courseIds
     * @return array{attempts: int, passes: int, failures: int, average_score: int|null, pass_rate: int|null, retries: int, completion: int}
     */
    private function overallMetrics(?Collection $studentIds, ?Collection $courseIds, ?ReportFilters $filters): array
    {
        $row = $this->baseQuery($studentIds, $courseIds, $filters)
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw("SUM(CASE WHEN a.status = 'passed' THEN 1 ELSE 0 END) as passes")
            ->selectRaw("SUM(CASE WHEN a.status = 'failed' THEN 1 ELSE 0 END) as failures")
            ->selectRaw('AVG(a.score) as average_score')
            ->first();

        $metrics = $this->present($row ?? $this->emptyRow());
        $pairs = $this->pairSummary($studentIds, $courseIds, $filters);

        $metrics['pass_rate'] = $pairs['terminal'] > 0
            ? (int) round(($pairs['passed'] / $pairs['terminal']) * 100)
            : null;
        $metrics['retries'] = $this->retryCounts($studentIds, $courseIds, $filters)->sum();
        $metrics['completion'] = $pairs['passed'];

        return $metrics;
    }

    /**
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $courseIds
     * @return list<array{
     *     assessment_id: int,
     *     course_id: int,
     *     title: string,
     *     attempts: int,
     *     passes: int,
     *     failures: int,
     *     average_score: int|null,
     *     pass_rate: int|null,
     *     retries: int,
     *     completion: int,
     * }>
     */
    private function metricsByAssessment(?Collection $studentIds, ?Collection $courseIds, ?ReportFilters $filters): array
    {
        $rows = $this->baseQuery($studentIds, $courseIds, $filters)
            ->selectRaw('a.assessment_id, s.course_id, s.title')
            ->selectRaw("SUM(CASE WHEN a.status = 'passed' THEN 1 ELSE 0 END) as passes")
            ->selectRaw("SUM(CASE WHEN a.status = 'failed' THEN 1 ELSE 0 END) as failures")
            ->selectRaw('AVG(a.score) as average_score')
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw('COUNT(DISTINCT a.user_id) as students')
            ->selectRaw('COUNT(DISTINCT CASE WHEN a.status = ? THEN a.user_id END) as passed_users', ['passed'])
            ->selectRaw("COUNT(DISTINCT CASE WHEN a.status IN ('passed', 'failed') THEN a.user_id END) as terminal_users")
            ->groupBy('a.assessment_id', 's.course_id', 's.title')
            ->orderBy('a.assessment_id')
            ->get();

        $retries = $this->retryCounts($studentIds, $courseIds, $filters);

        $listed = [];

        foreach ($rows as $row) {
            $metrics = $this->present($row);
            $assessmentId = (int) $row->assessment_id;

            $listed[] = [
                'assessment_id' => $assessmentId,
                'course_id' => (int) $row->course_id,
                'title' => (string) $row->title,
                ...$metrics,
                'pass_rate' => $row->terminal_users > 0
                    ? (int) round(((int) $row->passed_users / (int) $row->terminal_users) * 100)
                    : null,
                'retries' => $retries->get($assessmentId, 0),
                'completion' => (int) $row->passed_users,
            ];
        }

        return $listed;
    }

    /**
     * Distinct (user, assessment) pair summary behind pass rate and
     * completion: pairs with a passed row over pairs with a terminal row.
     *
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $courseIds
     * @return array{passed: int, terminal: int}
     */
    private function pairSummary(?Collection $studentIds, ?Collection $courseIds, ?ReportFilters $filters): array
    {
        $row = DB::query()->fromSub(
            $this->baseQuery($studentIds, $courseIds, $filters)
                ->selectRaw('a.user_id, a.assessment_id')
                ->selectRaw("MAX(CASE WHEN a.status = 'passed' THEN 1 ELSE 0 END) as has_passed")
                ->selectRaw("MAX(CASE WHEN a.status IN ('passed', 'failed') THEN 1 ELSE 0 END) as has_terminal")
                ->groupBy('a.user_id', 'a.assessment_id'),
            'pairs'
        )
            ->selectRaw('SUM(has_passed) as passed_pairs')
            ->selectRaw('SUM(has_terminal) as terminal_pairs')
            ->first();

        return [
            'passed' => (int) ($row->passed_pairs ?? 0),
            'terminal' => (int) ($row->terminal_pairs ?? 0),
        ];
    }

    /**
     * Retry counts keyed by assessment id. An in-range row counts as a retry
     * when an earlier row for the same pair exists anywhere in full history
     * (same authorization scope, no date bound); only in-range rows ever
     * contribute to counts.
     *
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, positive-int>
     */
    private function retryCounts(?Collection $studentIds, ?Collection $courseIds, ?ReportFilters $filters): Collection
    {
        $range = $this->baseQuery($studentIds, $courseIds, $filters)
            ->select(['a.id', 'a.user_id', 'a.assessment_id'])
            ->get();

        if ($range->isEmpty()) {
            return collect();
        }

        $firstIds = $this->baseQuery($studentIds, $courseIds, null)
            ->whereIn('a.user_id', $range->pluck('user_id')->unique())
            ->whereIn('a.assessment_id', $range->pluck('assessment_id')->unique())
            ->selectRaw('a.user_id, a.assessment_id, MIN(a.id) as first_id')
            ->groupBy('a.user_id', 'a.assessment_id')
            ->get()
            ->mapWithKeys(fn (stdClass $row): array => [
                $row->user_id.'.'.$row->assessment_id => (int) $row->first_id,
            ]);

        $counts = [];

        foreach ($range as $row) {
            if ($row->id <= $firstIds->get($row->user_id.'.'.$row->assessment_id, PHP_INT_MAX)) {
                continue;
            }

            $assessmentId = (int) $row->assessment_id;
            $counts[$assessmentId] = ($counts[$assessmentId] ?? 0) + 1;
        }

        return collect($counts);
    }

    /**
     * Shared core metrics. Pass rate is filled in by each caller from its
     * own pair grain.
     *
     * @return array{attempts: int, passes: int, failures: int, average_score: int|null, pass_rate: int|null}
     */
    private function present(stdClass $row): array
    {
        return [
            'attempts' => (int) ($row->attempts ?? 0),
            'passes' => (int) ($row->passes ?? 0),
            'failures' => (int) ($row->failures ?? 0),
            'average_score' => $row->average_score === null ? null : (int) round((float) $row->average_score),
            'pass_rate' => null,
        ];
    }

    private function emptyRow(): stdClass
    {
        return (object) [
            'attempts' => 0,
            'passes' => 0,
            'failures' => 0,
            'average_score' => null,
        ];
    }
}
