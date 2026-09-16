<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * System Analytics drill-down (US-710, §29.0/§30.0). Reuses, never re-derives:
 * top-line system metrics come from AdminDashboardService::metrics(), the
 * per-course table is the same CourseAnalyticsService::overview() collection
 * the dashboard sums over, and the XP administration figures come from
 * XpService::fleetSummary(). The only new SQL here is bounded and fleet-level:
 * the per-role breakdown, the raw mission-completion count, and the fleet-wide
 * assessment pass rate (the same has_passed / has_terminal student predicates
 * CourseAnalyticsService uses per course, aggregated across the fleet — a
 * single grouped query, never per-student work). Reading these rows writes
 * nothing — the page has no mutations.
 */
class AdminAnalyticsService
{
    public function __construct(
        private readonly AdminDashboardService $dashboard,
        private readonly CourseAnalyticsService $analytics,
        private readonly XpService $xp,
    ) {}

    /**
     * @return array{
     *     system: array{
     *         users_total: int,
     *         users_active: int,
     *         users_inactive: int,
     *         courses: int,
     *         sections: int,
     *         challenges: int,
     *         boss_challenges: int,
     *         attempts: int,
     *         passed_attempts: int,
     *         active_students: int,
     *         course_completions: int,
     *         completed_challenges: int,
     *         pass_rate: int|null,
     *     },
     *     roles: array<string, int>,
     *     xp: array{
     *         awarded: int,
     *         spent: int,
     *         deducted: int,
     *         outstanding: int,
     *         accounts: int,
     *         by_type: list<array{
     *             type: string,
     *             label: string,
     *             direction: 'award'|'spend'|'deduct',
     *             entries: int,
     *             total: int,
     *         }>,
     *     },
     *     courses: Collection<int, array{
     *         course: Course,
     *         fleet: int,
     *         engaged: int,
     *         buckets: array{completed: int, in_progress: int, assessment_ready: int, not_started: int},
     *         avg_completion: int|null,
     *         pass_rate: int|null,
     *         distribution: array<int, int>,
     *     }>,
     * }
     */
    public function overview(): array
    {
        $courses = $this->analytics->overview();

        return [
            'system' => [...$this->dashboard->metrics($courses), ...$this->fleetLearningStats()],
            'roles' => $this->roleBreakdown(),
            'xp' => $this->xp->fleetSummary(),
            'courses' => $courses,
        ];
    }

    /**
     * The §29.0 Learning lines that no existing aggregate covers: a fleet-wide
     * count of completed challenges (mission completions, i.e. the404_progress
     * rows, distinct from course completions) and the fleet-wide assessment
     * pass rate (distinct students who ever passed / distinct students with a
     * terminal passed-or-failed attempt; null when no terminal attempt exists —
     * the exact vocabulary CourseAnalyticsService uses per course, at fleet
     * level, one grouped query).
     *
     * @return array{completed_challenges: int, pass_rate: int|null}
     */
    private function fleetLearningStats(): array
    {
        $attempts = DB::table('the404_assessment_attempts')->selectRaw(
            'user_id, '
            .'MAX(CASE WHEN status = ? THEN 1 ELSE 0 END) as has_passed, '
            .'MAX(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as has_terminal',
            ['passed', 'passed', 'failed'],
        )->groupBy('user_id')->get();

        $passed = $attempts->sum(fn (object $row): int => (int) $row->has_passed);
        $terminal = $attempts->sum(fn (object $row): int => (int) $row->has_terminal);

        return [
            'completed_challenges' => (int) DB::table('the404_progress')->count(),
            'pass_rate' => $terminal > 0 ? (int) round($passed / $terminal * 100) : null,
        ];
    }

    /**
     * Fleet accounts by role. Normalized to the full ROLES set so every key
     * is always present for the panel, zero for unpopulated roles.
     *
     * @return array<string, int>
     */
    private function roleBreakdown(): array
    {
        $counts = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $breakdown = [];

        foreach (UserService::ROLES as $role) {
            $breakdown[$role] = (int) ($counts[$role] ?? 0);
        }

        return $breakdown;
    }
}
