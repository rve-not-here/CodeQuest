<?php

namespace App\Services;

use App\Support\ReportFilters;
use App\Support\SchemaAvailability;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Coding-challenge analytics (US-1006). Read-only descriptive metrics over
 * persisted mission activity. Nothing here validates, scores, unlocks, or
 * mutates; submission stays entirely inside MissionService.
 *
 * Metric definitions (one authoritative wording shared by UI, CSV, and PDF):
 *
 * - attempts: persisted submission rows in scope: Progress completions,
 *   wrong_submission XP rows, and grading_unavailable Activity rows. A
 *   grading_unavailable row is an infrastructure failure recorded when the
 *   behavioural grader could not decide a submission: it is an attempt, but
 *   neither a completion nor a student failure. Population: rows. Time range:
 *   completed_at for completions, created_at for failures and ungradable
 *   rows, each inside the filter range. Drafts are unsubmitted work,
 *   hint/solution XP rows are purchases, and post-completion resubmits persist
 *   nothing — none of them count.
 * - completions: Progress rows. Unique per (user, mission) by constraint,
 *   so one student completing two challenges counts twice and repeat
 *   passes never double-count.
 * - failures: wrong_submission XP rows. Exactly one row is written per
 *   failed submit, including zero-amount clamped rows at empty balance;
 *   amounts are never consulted. Ungradable rows are deliberately excluded:
 *   a grader outage is not a wrong answer.
 * - completion_rate: rounded integer percent of distinct completed
 *   (user, mission) pairs among distinct attempted pairs (a pair with at
 *   least one completion or failure row), null when no pair was attempted.
 *   Pair grain, never bare users: one student completing one of two
 *   attempted challenges reads 50, not 100.
 * - average_attempts: rounded integer mean submissions per attempted pair
 *   (completions plus failures over distinct attempted pairs), null when
 *   no pair was attempted.
 * - difficulty indicators: each challenge renders its stored difficulty
 *   label (EASY/MEDIUM/HARD) beside its empirical failure rate
 *   (failures over attempts for that challenge). Labels describe the
 *   mission, not the attempt; no difficulty score is invented.
 *
 * Deliberately absent: average score (no per-attempt mission scores are
 * stored), points averages (not in the spec list), and time metrics (no
 * durations are stored).
 *
 * Scopes follow the analytics convention: null student/course sets mean
 * unconstrained (admin fleet); ReportFilters narrow within the authorized
 * scope and never widen it. Missions carry no status column, so no status
 * vocabulary is registered and validated filters can never carry one.
 * Authorization itself belongs to ReportAuthorizationService and runs
 * before this service is called.
 */
class ChallengeAnalyticsService
{
    /**
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $courseIds
     * @return array{
     *     attempts: int,
     *     completions: int,
     *     failures: int,
     *     completion_rate: int|null,
     *     average_attempts: int|null,
     *     by_challenge: list<array{
     *         mission_id: int,
     *         course_id: int,
     *         title: string,
     *         difficulty: string,
     *         attempts: int,
     *         completions: int,
     *         failures: int,
     *         completion_rate: int|null,
     *         average_attempts: int|null,
     *         failure_rate: int|null,
     *     }>,
     * }
     */
    public function summarize(
        ?Collection $studentIds = null,
        ?Collection $courseIds = null,
        ?ReportFilters $filters = null,
    ): array {
        [$studentIds, $courseIds] = $this->applyFilters($studentIds, $courseIds, $filters);

        $missions = $this->scopedMissions($courseIds);
        $donePairs = $this->completedPairs($studentIds, $courseIds, $filters);
        $failRows = $this->failureRows($studentIds, $courseIds, $filters);
        $ungradableRows = $this->ungradableRows($studentIds, $courseIds, $filters);

        return $this->summarizeFrom($missions, $donePairs, $failRows, $ungradableRows);
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
     * Scoped missions with display context, ordered for stable output.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, stdClass>
     */
    private function scopedMissions(?Collection $courseIds): Collection
    {
        $query = DB::table('the404_missions as m')
            ->select(['m.id', 'm.course_id', 'm.title', 'm.difficulty']);

        if ($courseIds !== null) {
            $query->whereIn('m.course_id', $courseIds);
        }

        return $query->orderBy('m.id')->get();
    }

    /**
     * Distinct completed (user, mission) pairs in scope. Progress is unique
     * per pair by constraint, so one row always equals one completion.
     *
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, stdClass>
     */
    private function completedPairs(?Collection $studentIds, ?Collection $courseIds, ?ReportFilters $filters): Collection
    {
        $query = DB::table('the404_progress as p')
            ->join('the404_missions as m', 'm.id', '=', 'p.mission_id')
            ->select(['p.user_id', 'p.mission_id']);

        if ($studentIds !== null) {
            $query->whereIn('p.user_id', $studentIds);
        }

        if ($courseIds !== null) {
            $query->whereIn('m.course_id', $courseIds);
        }

        if ($filters !== null) {
            $filters->applyDateRange($query, 'p.completed_at');
        }

        return $query->distinct()->get();
    }

    /**
     * Every failure row in scope. One row exists per failed submit, so rows
     * — not pairs — are counted for failures.
     *
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, stdClass>
     */
    private function failureRows(?Collection $studentIds, ?Collection $courseIds, ?ReportFilters $filters): Collection
    {
        $query = DB::table('the404_xp_transactions as x')
            ->join('the404_missions as m', 'm.id', '=', 'x.mission_id')
            ->where('x.type', XpService::TYPE_WRONG_SUBMISSION)
            ->select(['x.user_id', 'x.mission_id']);

        if ($studentIds !== null) {
            $query->whereIn('x.user_id', $studentIds);
        }

        if ($courseIds !== null) {
            $query->whereIn('m.course_id', $courseIds);
        }

        if ($filters !== null) {
            $filters->applyDateRange($query, 'x.created_at');
        }

        return $query->get();
    }

    /**
     * Ungradable submissions in scope: grading_unavailable Activity rows
     * carrying a mission id. Historical rows written before the attribute
     * existed have no mission_id and cannot be placed — they stay uncounted
     * rather than guessed from message text.
     *
     * @param  Collection<int, int>|null  $studentIds
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, stdClass>
     */
    private function ungradableRows(?Collection $studentIds, ?Collection $courseIds, ?ReportFilters $filters): Collection
    {
        if (! SchemaAvailability::activityMissionId()) {
            // The attribution column is still pending its migration: an
            // ungradable row cannot be placed against a challenge, so it stays
            // uncounted rather than guessed.
            return collect();
        }

        $query = DB::table('the404_activity as a')
            ->join('the404_missions as m', 'm.id', '=', 'a.mission_id')
            ->where('a.type', 'grading_unavailable')
            ->whereNotNull('a.mission_id')
            ->select(['a.user_id', 'a.mission_id']);

        if ($studentIds !== null) {
            $query->whereIn('a.user_id', $studentIds);
        }

        if ($courseIds !== null) {
            $query->whereIn('m.course_id', $courseIds);
        }

        if ($filters !== null) {
            $filters->applyDateRange($query, 'a.created_at');
        }

        return $query->get();
    }

    /**
     * Distinct attempted pairs: the union of completed, failed, and
     * ungradable pairs.
     *
     * @param  Collection<int, stdClass>  $donePairs
     * @param  Collection<int, stdClass>  $failRows
     * @param  Collection<int, stdClass>  $ungradableRows
     * @return Collection<int, non-falsy-string>
     */
    private function attemptedPairs(Collection $donePairs, Collection $failRows, Collection $ungradableRows): Collection
    {
        return $donePairs
            ->map(fn (stdClass $row): string => $row->user_id.'.'.$row->mission_id)
            ->merge($failRows->map(fn (stdClass $row): string => $row->user_id.'.'.$row->mission_id))
            ->merge($ungradableRows->map(fn (stdClass $row): string => $row->user_id.'.'.$row->mission_id))
            ->unique()
            ->values();
    }

    /**
     * @return array{attempts: int, completions: int, failures: int, completion_rate: int|null, average_attempts: int|null}
     */
    private function present(
        int $attempts,
        int $completions,
        int $failures,
        int $attemptedPairs,
        int $completedPairs,
    ): array {
        return [
            'attempts' => $attempts,
            'completions' => $completions,
            'failures' => $failures,
            'completion_rate' => $attemptedPairs > 0 ? (int) round(($completedPairs / $attemptedPairs) * 100) : null,
            'average_attempts' => $attemptedPairs > 0 ? (int) round($attempts / $attemptedPairs) : null,
        ];
    }

    /**
     * Per-challenge rows for attempted missions only: a mission with no
     * completion, failure, or ungradable submission contributes nothing to
     * any metric.
     *
     * @param  Collection<int, stdClass>  $missions
     * @param  Collection<int, stdClass>  $donePairs
     * @param  Collection<int, stdClass>  $failRows
     * @param  Collection<int, stdClass>  $ungradableRows
     * @return list<array{
     *     mission_id: int,
     *     course_id: int,
     *     title: string,
     *     difficulty: string,
     *     attempts: int,
     *     completions: int,
     *     failures: int,
     *     completion_rate: int|null,
     *     average_attempts: int|null,
     *     failure_rate: int|null,
     * }>
     */
    private function presentByChallenge(
        Collection $missions,
        Collection $donePairs,
        Collection $failRows,
        Collection $ungradableRows,
    ): array {
        $doneByMission = $donePairs->groupBy('mission_id');
        $failByMission = $failRows->groupBy('mission_id');
        $ungradableByMission = $ungradableRows->groupBy('mission_id');

        $listed = [];

        foreach ($missions as $mission) {
            $missionId = (int) $mission->id;
            $done = $doneByMission->get($missionId, collect());
            $failed = $failByMission->get($missionId, collect());
            $ungradable = $ungradableByMission->get($missionId, collect());

            if ($done->isEmpty() && $failed->isEmpty() && $ungradable->isEmpty()) {
                continue;
            }

            $attemptedUsers = $done
                ->pluck('user_id')
                ->merge($failed->pluck('user_id'))
                ->merge($ungradable->pluck('user_id'))
                ->unique();
            $completedUsers = $done->pluck('user_id')->unique();
            $attempts = $done->count() + $failed->count() + $ungradable->count();

            $listed[] = [
                'mission_id' => $missionId,
                'course_id' => (int) $mission->course_id,
                'title' => (string) $mission->title,
                'difficulty' => (string) $mission->difficulty,
                ...$this->present(
                    $attempts,
                    $done->count(),
                    $failed->count(),
                    $attemptedUsers->count(),
                    $completedUsers->count(),
                ),
                'failure_rate' => (int) round(($failed->count() / $attempts) * 100),
            ];
        }

        return $listed;
    }

    /**
     * @param  Collection<int, stdClass>  $missions
     * @param  Collection<int, stdClass>  $donePairs
     * @param  Collection<int, stdClass>  $failRows
     * @param  Collection<int, stdClass>  $ungradableRows
     * @return array{attempts: int, completions: int, failures: int, completion_rate: int|null, average_attempts: int|null, by_challenge: list<array{mission_id: int, course_id: int, title: string, difficulty: string, attempts: int, completions: int, failures: int, completion_rate: int|null, average_attempts: int|null, failure_rate: int|null}>}
     */
    private function summarizeFrom(
        Collection $missions,
        Collection $donePairs,
        Collection $failRows,
        Collection $ungradableRows,
    ): array {
        $attempted = $this->attemptedPairs($donePairs, $failRows, $ungradableRows);
        $completedUsersByPair = $donePairs
            ->map(fn (stdClass $row): string => $row->user_id.'.'.$row->mission_id)
            ->unique();

        $overall = $this->present(
            $donePairs->count() + $failRows->count() + $ungradableRows->count(),
            $donePairs->count(),
            $failRows->count(),
            $attempted->count(),
            $completedUsersByPair->count(),
        );

        return [...$overall, 'by_challenge' => $this->presentByChallenge($missions, $donePairs, $failRows, $ungradableRows)];
    }
}
