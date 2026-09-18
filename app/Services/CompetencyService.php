<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Support\Collection;

/**
 * Builds the competency overview (US-507): one competency per active course
 * with missions, all derived server-side from real learning and assessment
 * data. XP figures play no part in any competency value (§23.0); the only
 * wrong-submission signal reads the transaction log's penalty rows as a
 * per-mission engagement journal, and their amounts are never consulted.
 *
 * The small state model (§26.0), evaluated in order per course:
 *   NOT STARTED   -> no completed mission, no wrong submission, no attempt
 *   DEVELOPING    -> first mission completed or first wrong submission, with
 *                    fewer than half the course's missions done
 *   PRACTICING    -> at least half the missions done, or the Boss Challenge
 *                    attempted without a pass yet
 *   DEMONSTRATED  -> every mission completed and the Boss Challenge passed
 *
 * Monotonicity mirrors US-409/410: DEMONSTRATED reads hasPassed(), which is
 * attempt-history based, and Progress rows are never deleted, so a failed
 * retry after the first pass can never downgrade a competency.
 */
class CompetencyService
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly AssessmentService $assessments,
    ) {}

    /**
     * An optional $courseIds scope restricts the overview to a monitorable
     * course set (a teacher's shared classrooms). null keeps every active
     * course with missions; an empty Collection yields no rows.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, array{
     *     course: Course,
     *     name: string,
     *     state: 'not_started'|'developing'|'practicing'|'demonstrated',
     *     completedMissions: int,
     *     totalMissions: int,
     *     percent: int,
     *     wrongSubmissions: int,
     *     attempts: int,
     *     challengePassed: bool,
     * }>
     */
    public function overview(User $user, ?Collection $courseIds = null): Collection
    {
        return Course::query()
            ->where('status', 'active')
            ->orderBy('order_num')
            ->with('missions')
            ->when($courseIds !== null, fn ($query) => $query->whereIn('id', $courseIds))
            ->get()
            ->filter(fn (Course $course): bool => $course->missions->isNotEmpty())
            ->map(fn (Course $course): array => $this->summarise($user, $course))
            ->values();
    }

    /**
     * @return array{
     *     course: Course,
     *     name: string,
     *     state: 'not_started'|'developing'|'practicing'|'demonstrated',
     *     completedMissions: int,
     *     totalMissions: int,
     *     percent: int,
     *     wrongSubmissions: int,
     *     attempts: int,
     *     challengePassed: bool,
     * }
     */
    private function summarise(User $user, Course $course): array
    {
        $progress = $this->dashboard->courseProgress($user, $course);
        $missionIds = $course->missions->pluck('id');
        $attempts = $this->attemptCount($user, $course);

        return [
            'course' => $course,
            'name' => $this->nameFor($course->type),
            'state' => $this->stateFor(
                $progress['completed'],
                $progress['total'],
                $attempts,
                $this->wrongSubmissionCount($user, $missionIds),
                $this->assessments->hasPassed($user, $course),
            ),
            'completedMissions' => $progress['completed'],
            'totalMissions' => $progress['total'],
            'percent' => $progress['percent'],
            'wrongSubmissions' => $this->wrongSubmissionCount($user, $missionIds),
            'attempts' => $attempts,
            'challengePassed' => $this->assessments->hasPassed($user, $course),
        ];
    }

    /**
     * @return 'not_started'|'developing'|'practicing'|'demonstrated'
     */
    private function stateFor(int $done, int $total, int $attempts, int $wrongSubmissions, bool $passed): string
    {
        if ($done >= $total && $passed) {
            return 'demonstrated';
        }

        if ($done * 2 >= $total || $attempts > 0) {
            return 'practicing';
        }

        if ($done > 0 || $wrongSubmissions > 0) {
            return 'developing';
        }

        return 'not_started';
    }

    private function attemptCount(User $user, Course $course): int
    {
        $assessment = $this->assessments->forCourse($course);

        if ($assessment === null) {
            return 0;
        }

        return (int) AssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->count();
    }

    /**
     * Wrong submissions on the course's own missions. The only per-mission
     * record of a wrong submission is the penalty row in the transaction log;
     * it is queried strictly as an engagement journal (presence/count), never
     * for its amount.
     *
     * @param  iterable<int, mixed>  $missionIds
     */
    private function wrongSubmissionCount(User $user, iterable $missionIds): int
    {
        return (int) XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_WRONG_SUBMISSION)
            ->whereIn('mission_id', $missionIds)
            ->count();
    }

    private function nameFor(string $type): string
    {
        return match (strtolower($type)) {
            'html' => 'HTML',
            'css' => 'CSS',
            'js' => 'JavaScript',
            default => strtoupper($type),
        };
    }
}
