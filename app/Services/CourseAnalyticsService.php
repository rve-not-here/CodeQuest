<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Per-course aggregate analytics for the teacher area (US-607, §24.0-§26.0).
 *
 * Every metric is calculated from real records in SQL-level aggregation queries
 * (GROUP BY over the progress and assessment-attempt tables), never by
 * iterating the roster through the per-student services. The state vocabulary
 * still matches the student-facing services exactly: COMPLETED is
 * hasPassed() (a history 'passed' attempt), READY is isUnlocked() (all
 * missions done AND an active assessment, so a student who finished a course
 * whose challenge is not open stays in-progress), and engagement is any
 * Progress row on the course. CourseAnalyticsTest locks that equivalence by
 * comparing every fixture student's bucket against the per-student services.
 *
 * The bucket counts partition the student fleet per course, so NOT-STARTED is
 * the fleet minus the engaged, and the four counts always sum to the fleet
 * size. Courses render in order_num; locked/draft courses and courses with
 * zero missions are excluded (the same universe the student side renders).
 */
class CourseAnalyticsService
{
    /**
     * Completion-percentage band labels for the distribution (§53.0). A
     * histogram is the one chart that genuinely adds information over the four
     * bucket counts: bucket counts hide whether in-progress students cluster
     * at the start, spread across the course, or sit just short of the
     * challenge. The distribution is keyed by band order (0..4), not by these
     * labels, because PHP coerces the numeric "100" label to an integer key.
     */
    public const DISTRIBUTION_BANDS = ['0-24', '25-49', '50-74', '75-99', '100'];

    /**
     * The bucket counts partition the student fleet per course, so NOT-STARTED is
     * the fleet minus the engaged, and the four counts always sum to the fleet
     * size. Courses render in order_num; locked/draft courses and courses with
     * zero missions are excluded (the same universe the student side renders).
     */
    public function __construct(
        private readonly ClassroomAccessService $access,
    ) {}

    /**
     * @param  Collection<int, int>|null  $studentIds  restrict the fleet to a monitorable student set (null = whole fleet)
     * @param  Collection<int, int>|null  $courseIds  restrict to a monitorable course set (null = every active course)
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes  the per-student shared-course
     *                                                                      map from ClassroomAccessService::scopesFor.
     *                                                                      when provided it wins over the two flat
     *                                                                      sets: rows are confined to the exact
     *                                                                      Student/Course pairs, and each course's
     *                                                                      fleet is the students who may monitor it,
     *                                                                      never the whole monitorable student set.
     * @return Collection<int, array{
     *     course: Course,
     *     fleet: int,
     *     engaged: int,
     *     buckets: array{completed: int, in_progress: int, assessment_ready: int, not_started: int},
     *     avg_completion: int|null,
     *     pass_rate: int|null,
     *     distribution: array<int, int>,
     * }>
     */
    public function overview(?Collection $studentIds = null, ?Collection $courseIds = null, ?array $studentCourseScopes = null): Collection
    {
        $fleet = $this->fleetSize($studentIds);

        if ($studentCourseScopes !== null) {
            $courseIds = collect($studentCourseScopes)
                ->flatMap(fn (Collection $ids): Collection => $ids)
                ->unique()
                ->values();
        }

        $missionCounts = Mission::query()
            ->toBase()
            ->selectRaw('course_id, COUNT(*) as total')
            ->groupBy('course_id')
            ->pluck('total', 'course_id');

        $progressByCourse = DB::table('the404_progress as p')
            ->join('the404_missions as m', 'm.id', '=', 'p.mission_id')
            ->selectRaw('m.course_id as course_id, p.user_id as user_id, COUNT(DISTINCT p.mission_id) as done')
            ->groupBy('m.course_id', 'p.user_id');

        if ($studentCourseScopes !== null) {
            $progressByCourse = $this->access->whereAllowedPairs($progressByCourse, 'p.user_id', 'm.course_id', $studentCourseScopes);
        } elseif ($studentIds !== null) {
            $progressByCourse->whereIn('p.user_id', $studentIds);
        }

        $progressByCourse = $progressByCourse->get()->groupBy('course_id');

        $attemptsByCourse = DB::table('the404_assessment_attempts as at')
            ->join('the404_assessments as a', 'a.id', '=', 'at.assessment_id')
            ->selectRaw(
                'a.course_id as course_id, at.user_id as user_id, '
                .'MAX(CASE WHEN at.status = ? THEN 1 ELSE 0 END) as has_passed, '
                .'MAX(CASE WHEN at.status IN (?, ?) THEN 1 ELSE 0 END) as has_terminal',
                ['passed', 'passed', 'failed'],
            )
            ->groupBy('a.course_id', 'at.user_id');

        if ($studentCourseScopes !== null) {
            $attemptsByCourse = $this->access->whereAllowedPairs($attemptsByCourse, 'at.user_id', 'a.course_id', $studentCourseScopes);
        } elseif ($studentIds !== null) {
            $attemptsByCourse->whereIn('at.user_id', $studentIds);
        }

        $attemptsByCourse = $attemptsByCourse->get()->groupBy('course_id');

        $assessmentStatuses = Assessment::query()
            ->pluck('status', 'course_id');

        $courses = Course::query()
            ->where('status', 'active')
            ->orderBy('order_num')
            ->orderBy('id')
            ->when($courseIds !== null, fn ($query) => $query->whereIn('id', $courseIds))
            ->get()
            ->filter(fn (Course $course): bool => ((int) ($missionCounts->get($course->id) ?? 0)) > 0);

        $scopedCourseFleet = $studentCourseScopes !== null
            ? $this->courseFleetByScope($studentCourseScopes)
            : null;

        return $courses->map(fn (Course $course): array => $this->course(
            $course,
            $scopedCourseFleet !== null ? ($scopedCourseFleet[$course->id] ?? 0) : $fleet,
            (int) ($missionCounts->get($course->id) ?? 0),
            $progressByCourse->get($course->id, collect()),
            $attemptsByCourse->get($course->id, collect()),
            $assessmentStatuses->get($course->id),
        ))->values();
    }

    /**
     * The fleet of a course under a per-student scope is NOT the whole
     * monitorable student set: it is the students allowed to view THIS course.
     * NOT-STARTED is that per-course fleet minus the engaged, so the four
     * buckets always sum to the fleet a given teacher may actually see.
     *
     * @param  array<int, Collection<int, int>>  $studentCourseScopes
     * @return array<int, int> course_id => number of students who may monitor it
     */
    private function courseFleetByScope(array $studentCourseScopes): array
    {
        $fleet = [];

        foreach ($studentCourseScopes as $courseIds) {
            foreach ($courseIds as $courseId) {
                $fleet[$courseId] = ($fleet[$courseId] ?? 0) + 1;
            }
        }

        return $fleet;
    }

    /**
     * @param  Collection<int, \stdClass>  $progressRows
     * @param  Collection<int, \stdClass>  $attemptRows
     * @return array{
     *     course: Course,
     *     fleet: int,
     *     engaged: int,
     *     buckets: array{completed: int, in_progress: int, assessment_ready: int, not_started: int},
     *     avg_completion: int|null,
     *     pass_rate: int|null,
     *     distribution: array<int, int>,
     * }
     */
    private function course(
        Course $course,
        int $fleet,
        int $total,
        Collection $progressRows,
        Collection $attemptRows,
        ?string $assessmentStatus,
    ): array {
        $assessmentReady = $assessmentStatus === 'active';

        $attemptsById = $attemptRows->keyBy('user_id');
        $passedUserIds = $attemptsById
            ->filter(fn (object $row): bool => (int) $row->has_passed === 1)
            ->keys();
        $passedSet = $passedUserIds->flip();

        $distribution = array_fill(0, count(self::DISTRIBUTION_BANDS), 0);
        $inProgress = 0;
        $ready = 0;
        $completionPercent = [];

        foreach ($progressRows as $row) {
            $done = (int) $row->done;
            $percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;

            $completionPercent[] = $percent;
            $distribution[$this->bandIndexFor($percent)]++;

            if ($passedSet->has($row->user_id)) {
                continue;
            }

            if ($assessmentReady && $done === $total) {
                $ready++;
            } else {
                $inProgress++;
            }
        }

        $terminalAttemptCount = $attemptsById
            ->filter(fn (object $row): bool => (int) $row->has_terminal === 1)
            ->count();

        $passRate = $terminalAttemptCount > 0
            ? (int) round(($passedUserIds->count() / $terminalAttemptCount) * 100)
            : null;

        $engaged = $progressRows->count();

        return [
            'course' => $course,
            'fleet' => $fleet,
            'engaged' => $engaged,
            'buckets' => [
                'completed' => $passedUserIds->count(),
                'in_progress' => $inProgress,
                'assessment_ready' => $ready,
                'not_started' => max(0, $fleet - $engaged),
            ],
            'avg_completion' => $engaged > 0 ? (int) round(array_sum($completionPercent) / $engaged) : null,
            'pass_rate' => $passRate,
            'distribution' => $distribution,
        ];
    }

    /**
     * Order index into DISTRIBUTION_BANDS for a completion percentage.
     */
    private function bandIndexFor(int $percent): int
    {
        if ($percent >= 100) {
            return 4;
        }

        if ($percent >= 75) {
            return 3;
        }

        if ($percent >= 50) {
            return 2;
        }

        if ($percent >= 25) {
            return 1;
        }

        return 0;
    }

    /**
     * @param  Collection<int, int>|null  $studentIds
     */
    private function fleetSize(?Collection $studentIds = null): int
    {
        if ($studentIds !== null) {
            return $studentIds->count();
        }

        return User::query()->where('role', 'student')->count();
    }
}
