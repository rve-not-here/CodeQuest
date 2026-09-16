<?php

namespace App\Services;

use App\Models\AdminAudit;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Admin console overview (US-702, §8.0). Not a second reporting engine — the
 * learning figures reuse the exact same aggregates the teacher area renders:
 *
 *   - active students           TeacherDashboardService::countActiveStudents
 *                               (the four-source learning-event UNION)
 *   - assessments passed        TeacherDashboardService::countPassedAssessments
 *   - course completions        sum of CourseAnalyticsService::overview()
 *                               bucket 'completed' (distinct students who
 *                               passed each active course — the same universe
 *                               the analytics page renders)
 *
 * Only the account and catalog counts are new (total/active/inactive users,
 * courses/sections/challenges, attempts), and each is a single bounded query,
 * mirroring the teacher dashboard's no-fan-out discipline. "Recent system
 * activity" always comes from the dedicated AdminAuditService — never from
 * TimelineService, whose learning-beat vocabulary is a different concept.
 */
class AdminDashboardService
{
    public function __construct(
        private readonly TeacherDashboardService $teacher,
        private readonly CourseAnalyticsService $analytics,
        private readonly AdminAuditService $audit,
    ) {}

    /**
     * @return array{
     *     metrics: array{
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
     *     },
     *     recent_system_activity: Collection<int, AdminAudit>,
     * }
     */
    public function overview(int $activityLimit = 10): array
    {
        return [
            'metrics' => $this->metrics(),
            'recent_system_activity' => $this->audit->recent($activityLimit),
        ];
    }

    /**
     * Fleet-wide top-line metrics, reusable by the admin console and the
     * System Analytics drill-down (US-710). Pass an already-fetched
     * CourseAnalyticsService::overview() collection to avoid recomputing the
     * course aggregates twice on one page; the sum below stays the single
     * source of the course-completions figure either way.
     *
     * @param  Collection<int, array{
     *     course: Course,
     *     fleet: int,
     *     engaged: int,
     *     buckets: array{completed: int, in_progress: int, assessment_ready: int, not_started: int},
     *     avg_completion: int|null,
     *     pass_rate: int|null,
     *     distribution: array<int, int>,
     * }>|null  $courseOverview
     * @return array{
     *     users_total: int,
     *     users_active: int,
     *     users_inactive: int,
     *     courses: int,
     *     sections: int,
     *     challenges: int,
     *     boss_challenges: int,
     *     attempts: int,
     *     passed_attempts: int,
     *     active_students: int,
     *     course_completions: int,
     * }
     */
    public function metrics(?Collection $courseOverview = null): array
    {
        return [
            'users_total' => User::query()->count(),
            'users_active' => User::query()->where('status', 'active')->count(),
            'users_inactive' => User::query()->where('status', 'inactive')->count(),
            'courses' => Course::query()->count(),
            'sections' => Section::query()->count(),
            'challenges' => Mission::query()->count(),
            'boss_challenges' => Assessment::query()->count(),
            'attempts' => (int) DB::table('the404_assessment_attempts')->count(),
            'passed_attempts' => $this->teacher->countPassedAssessments(),
            'active_students' => $this->teacher->countActiveStudents(),
            'course_completions' => $this->courseCompletions($courseOverview),
        ];
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
     * }>|null  $courseOverview
     */
    private function courseCompletions(?Collection $courseOverview = null): int
    {
        return ($courseOverview ?? $this->analytics->overview())
            ->sum(fn (array $row): int => $row['buckets']['completed']);
    }
}
