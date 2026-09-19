<?php

namespace App\Services;

use App\Models\Assessment;
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
        $courses = Course::query()
            ->where('status', 'active')
            ->orderBy('order_num')
            ->with('missions')
            ->when($courseIds !== null, fn ($query) => $query->whereIn('id', $courseIds))
            ->get()
            ->filter(fn (Course $course): bool => $course->missions->isNotEmpty())
            ->values();

        if ($courses->isEmpty()) {
            return collect();
        }

        // US-911: all per-course evidence below is fetched in grouped reads.
        // Every predicate matches the single-course methods verbatim; only
        // the access pattern changes from one-query-per-course to constant.
        $progress = $this->dashboard->courseProgressMap($user, $courses);
        $states = $this->assessments->assessmentStatesForCourses($user, $courses);

        $missionIds = $courses->flatMap(fn (Course $course) => $course->missions->pluck('id'));

        $wrongByMission = XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', XpService::TYPE_WRONG_SUBMISSION)
            ->whereIn('mission_id', $missionIds)
            ->selectRaw('mission_id, count(*) as wrong_count')
            ->groupBy('mission_id')
            ->pluck('wrong_count', 'mission_id');

        $assessmentIds = collect($states)
            ->map(fn (array $state) => $state['assessment'])
            ->filter()
            ->map(fn (Assessment $assessment): int => $assessment->id);

        $attemptsByAssessment = AssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('assessment_id', $assessmentIds)
            ->selectRaw('assessment_id, count(*) as attempt_count')
            ->groupBy('assessment_id')
            ->pluck('attempt_count', 'assessment_id');

        return $courses
            ->map(function (Course $course) use ($progress, $states, $wrongByMission, $attemptsByAssessment): array {
                // The map covers every input course; the default matches
                // zero-mission math (0/0 → 0%) and only satisfies the type.
                $courseProgress = $progress->get($course->id) ?? ['completed' => 0, 'total' => 0, 'percent' => 0];
                $state = $states[$course->id];

                $wrongSubmissions = $course->missions
                    ->pluck('id')
                    ->sum(fn (int $missionId): int => (int) $wrongByMission->get($missionId, 0));

                $attempts = $state['assessment'] !== null
                    ? (int) $attemptsByAssessment->get($state['assessment']->id, 0)
                    : 0;

                return [
                    'course' => $course,
                    'name' => $this->nameFor($course->type),
                    'state' => $this->stateFor(
                        $courseProgress['completed'],
                        $courseProgress['total'],
                        $attempts,
                        $wrongSubmissions,
                        $state['passed'],
                    ),
                    'completedMissions' => $courseProgress['completed'],
                    'totalMissions' => $courseProgress['total'],
                    'percent' => $courseProgress['percent'],
                    'wrongSubmissions' => $wrongSubmissions,
                    'attempts' => $attempts,
                    'challengePassed' => $state['passed'],
                ];
            })
            ->values();
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
