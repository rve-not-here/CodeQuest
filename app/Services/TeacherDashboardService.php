<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
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
        private readonly ClassroomAccessService $access,
    ) {}

    /**
     * @param  int  $limit  number of recent fleet beats to surface
     * @param  Collection<int, int>|null  $studentIds  restrict to a monitorable student set (null = whole fleet)
     * @param  Collection<int, int>|null  $courseIds  restrict to a monitorable course set (null = every active course)
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes  per-student shared-course ids for a scoped teacher (null = unrestricted)
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
    public function overview(int $limit = 8, ?Collection $studentIds = null, ?Collection $courseIds = null, ?array $studentCourseScopes = null): array
    {
        $summary = $this->analytics->overview($studentIds, $courseIds, $studentCourseScopes);
        $attention = $this->attention->list($studentIds, $studentCourseScopes);

        return [
            'metrics' => [
                'total_students' => $this->countStudents($studentIds, $studentCourseScopes),
                'active_students' => $this->countActiveStudents($studentIds, $studentCourseScopes),
                'courses_in_progress' => $this->coursesInProgress($summary),
                'assessments_passed' => $this->countPassedAssessments($studentIds, $studentCourseScopes),
                'needs_attention' => $attention->count(),
            ],
            'recent_activity' => $this->recentActivity($limit, $studentIds, $studentCourseScopes),
            'assessment_summary' => $summary,
        ];
    }

    /**
     * @param  Collection<int, int>|null  $studentIds
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     */
    private function countStudents(?Collection $studentIds = null, ?array $studentCourseScopes = null): int
    {
        if ($studentCourseScopes !== null) {
            return count($studentCourseScopes);
        }

        if ($studentIds !== null) {
            return $studentIds->count();
        }

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

    /**
     * @param  Collection<int, int>|null  $studentIds
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     */
    public function countPassedAssessments(?Collection $studentIds = null, ?array $studentCourseScopes = null): int
    {
        $query = DB::table('the404_assessment_attempts as at')
            ->join('the404_assessments as a', 'a.id', '=', 'at.assessment_id')
            ->where('at.status', 'passed');

        if ($studentCourseScopes !== null) {
            $query = $this->access->whereAllowedPairs($query, 'at.user_id', 'a.course_id', $studentCourseScopes);
        } elseif ($studentIds !== null) {
            $query->whereIn('at.user_id', $studentIds);
        }

        return (int) $query->count();
    }

    /**
     * A student is "active" when they have at least one learning event in the
     * last ACTIVE_WINDOW_DAYS. The window reuses TimelineService's default
     * feed window on purpose, so "active" means the same span the Learning
     * Activity strip renders below it. A learning event is one of the four
     * timeline sources — a mission completion, an assessment attempt, an
     * activity row, or an xp_transaction — with login/logout bookkeeping
     * excluded (TimelineService::NON_LEARNING_ACTIVITY_TYPES).
     *
     * Under a per-student course scope the definition is stricter: the event
     * must be attributable to an allowed Student/Course pair. Activity rows
     * carry no course link, so for a scoped teacher that source cannot count
     * and matches nothing.
     *
     * @param  Collection<int, int>|null  $studentIds
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     */
    public function countActiveStudents(?Collection $studentIds = null, ?array $studentCourseScopes = null): int
    {
        $cutoff = now()->startOfDay()->subDays(self::ACTIVE_WINDOW_DAYS);

        $activity = DB::table('the404_activity')
            ->select('user_id')
            ->where('created_at', '>=', $cutoff)
            ->whereNotIn('type', TimelineService::NON_LEARNING_ACTIVITY_TYPES);

        if ($studentCourseScopes !== null) {
            $activity->whereRaw('1 = 0');
        } elseif ($studentIds !== null) {
            $activity->whereIn('user_id', $studentIds);
        }

        $attempts = DB::table('the404_assessment_attempts as at')
            ->join('the404_assessments as a', 'a.id', '=', 'at.assessment_id')
            ->select('at.user_id')
            ->where(DB::raw('COALESCE(at.passed_at, at.submitted_at, at.created_at)'), '>=', $cutoff);

        if ($studentCourseScopes !== null) {
            $attempts = $this->access->whereAllowedPairs($attempts, 'at.user_id', 'a.course_id', $studentCourseScopes);
        } elseif ($studentIds !== null) {
            $attempts->whereIn('at.user_id', $studentIds);
        }

        $progress = DB::table('the404_progress as p')
            ->join('the404_missions as m', 'm.id', '=', 'p.mission_id')
            ->select('p.user_id')
            ->where('p.completed_at', '>=', $cutoff);

        if ($studentCourseScopes !== null) {
            $progress = $this->access->whereAllowedPairs($progress, 'p.user_id', 'm.course_id', $studentCourseScopes);
        } elseif ($studentIds !== null) {
            $progress->whereIn('p.user_id', $studentIds);
        }

        $xp = DB::table('the404_xp_transactions as x')
            ->select('x.user_id')
            ->where('x.created_at', '>=', $cutoff);

        if ($studentCourseScopes !== null) {
            $xp = $this->scopeXpToPairs($xp, $studentCourseScopes);
        } elseif ($studentIds !== null) {
            $xp->whereIn('x.user_id', $studentIds);
        }

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
     * Confine XP transactions to the monitorable Student/Course pairs. An XP
     * row links to a course through either its mission or its assessment, so
     * a row counts when that linked course is in the student's allowed set.
     *
     * @param  array<int, Collection<int, int>>  $studentCourseScopes
     */
    private function scopeXpToPairs(Builder $query, array $studentCourseScopes): Builder
    {
        return $query->where(function (Builder $query) use ($studentCourseScopes): void {
            if ($studentCourseScopes === []) {
                $query->whereRaw('1 = 0');

                return;
            }

            foreach ($studentCourseScopes as $userId => $courseIds) {
                $query->orWhere(function (Builder $query) use ($userId, $courseIds): void {
                    $query->where('x.user_id', $userId)->where(function (Builder $query) use ($courseIds): void {
                        $query
                            ->whereExists(function (Builder $query) use ($courseIds): void {
                                $query->selectRaw('1')
                                    ->from('the404_missions as m')
                                    ->whereColumn('m.id', 'x.mission_id')
                                    ->whereIn('m.course_id', $courseIds);
                            })
                            ->orWhereExists(function (Builder $query) use ($courseIds): void {
                                $query->selectRaw('1')
                                    ->from('the404_assessments as a')
                                    ->whereColumn('a.id', 'x.assessment_id')
                                    ->whereIn('a.course_id', $courseIds);
                            });
                    });
                });
            }
        });
    }

    /**
     * @param  Collection<int, int>|null  $studentIds
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int, user: array{id: int, username: string}}>
     */
    private function recentActivity(int $limit, ?Collection $studentIds = null, ?array $studentCourseScopes = null): Collection
    {
        $from = now()->startOfDay()->subDays(self::ACTIVE_WINDOW_DAYS);

        $feed = $this->timeline->feed($studentIds, null, null, $from, now(), [], $studentCourseScopes);

        return collect($feed->items())->take($limit)->values();
    }
}
