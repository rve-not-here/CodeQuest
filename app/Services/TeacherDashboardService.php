<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The teacher dashboard (US-609, §5.0/§52) is the composition story of the
 * phase, not a new reporting engine. It lands on the established teacher
 * landing page (/students, where AuthController::homeFor sends teachers after
 * login) and composes that roster from the same services the detail pages
 * already use:
 *
 *   - the roster itself            StudentService::index
 *   - the fleet activity strip     TimelineService::feed (same beat vocabulary
 *                                  as the /activity page)
 *   - the per-course assessment
 *     summary                      CourseAnalyticsService::overview
 *   - the needs-attention KPI      AttentionService::list — deliberately the
 *                                  SAME computation as /needs-attention, so the
 *                                  headline number and that page can never
 *                                  disagree with each other
 *
 * Only four aggregate counts are genuinely new (total students, active
 * students, courses with students mid-course, and assessments passed across
 * the fleet), and each is a single bounded query — no per-student fan-out.
 *
 * countActiveStudents() and countPassedAssessments() are PUBLIC because the
 * admin console (US-702, AdminDashboardService) reuses them for its learning
 * and assessment panels — the active-window UNION and the passed-attempt count
 * must have exactly one home so the teacher and admin pages can never disagree.
 */
class TeacherDashboardService
{
    /**
     * A student is "active" when they have at least one learning event in the
     * last ACTIVE_WINDOW_DAYS. The window reuses TimelineService's default
     * feed window on purpose, so "active" means the same span the Learning
     * Activity strip renders below it. A learning event is one of the four
     * timeline sources — a mission completion, an assessment attempt, an
     * activity row, or an xp_transaction — with login/logout bookkeeping
     * excluded (TimelineService::NON_LEARNING_ACTIVITY_TYPES).
     */
    public const ACTIVE_WINDOW_DAYS = TimelineService::DEFAULT_WINDOW_DAYS;

    public function __construct(
        private readonly CourseAnalyticsService $analytics,
        private readonly AttentionService $attention,
        private readonly TimelineService $timeline,
    ) {}

    /**
     * @param  int  $limit  number of recent fleet beats to surface
     * @return array{
     *     metrics: array{
     *         total_students: int,
     *         active_students: int,
     *         courses_in_progress: int,
     *         assessments_passed: int,
     *         needs_attention: int,
     *     },
     *     recent_activity: Collection<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int, user: array{id: int, username: string}}>,
     *     assessment_summary: Collection<int, array{
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
    public function overview(int $limit = 8): array
    {
        $summary = $this->analytics->overview();

        return [
            'metrics' => [
                'total_students' => $this->countStudents(),
                'active_students' => $this->countActiveStudents(),
                'courses_in_progress' => $this->coursesInProgress($summary),
                'assessments_passed' => $this->countPassedAssessments(),
                'needs_attention' => $this->attention->list()->count(),
            ],
            'recent_activity' => $this->recentActivity($limit),
            'assessment_summary' => $summary,
        ];
    }

    private function countStudents(): int
    {
        return User::query()->where('role', 'student')->count();
    }

    /**
     * @param  Collection<int, array{
     *     course: Course,
     *     fleet: int,
     *     engaged: int,
     *     buckets: array{completed: int, in_progress: int, assessment_ready: int, not_started: int},
     *     avg_completion: int|null,
     *     pass_rate: int|null,
     *     distribution: array<int, int>,
     * }>  $summary
     */
    private function coursesInProgress(Collection $summary): int
    {
        return $summary
            ->filter(fn (array $row): bool => $row['buckets']['in_progress'] > 0 || $row['buckets']['assessment_ready'] > 0)
            ->count();
    }

    public function countPassedAssessments(): int
    {
        return (int) DB::table('the404_assessment_attempts')
            ->where('status', 'passed')
            ->count();
    }

    public function countActiveStudents(): int
    {
        $cutoff = now()->startOfDay()->subDays(self::ACTIVE_WINDOW_DAYS);

        $activity = DB::table('the404_activity')
            ->select('user_id')
            ->where('created_at', '>=', $cutoff)
            ->whereNotIn('type', TimelineService::NON_LEARNING_ACTIVITY_TYPES);

        $attempts = DB::table('the404_assessment_attempts')
            ->select('user_id')
            ->where(DB::raw('COALESCE(passed_at, submitted_at, created_at)'), '>=', $cutoff);

        $progress = DB::table('the404_progress')
            ->select('user_id')
            ->where('completed_at', '>=', $cutoff);

        $xp = DB::table('the404_xp_transactions')
            ->select('user_id')
            ->where('created_at', '>=', $cutoff);

        $union = $progress
            ->union($activity)
            ->union($attempts)
            ->union($xp);

        return (int) DB::query()
            ->fromSub($union, 'active_users')
            ->distinct()
            ->count('user_id');
    }

    /**
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int, user: array{id: int, username: string}}>
     */
    private function recentActivity(int $limit): Collection
    {
        $from = now()->startOfDay()->subDays(self::ACTIVE_WINDOW_DAYS);

        $feed = $this->timeline->feed(null, null, null, $from, now(), []);

        return collect($feed->items())->take($limit)->values();
    }
}
