<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Mission;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Students Needing Attention (US-608, §19.0-§21.0).
 *
 * Attention signals are deterministic, documented, and explainable, and are
 * deliberately NOT an academic grade. Every signal is a binary rule with a
 * named threshold and an evidence string. A student is listed when ANY signal
 * fires: an OR union, never a weighted composite. A weighted score would read
 * as a grade to a teacher, which §21.0 forbids, and every threshold here
 * anchors to the product's own rubric (retry affordance, mission length,
 * passing_score); there is no intervention-outcome data anywhere to calibrate
 * weights against.
 *
 * Everything is derived from fleet-level aggregations over the progress,
 * attempt, and assessment tables, never per-student service fan-out, matching
 * CourseAnalyticsService (US-607). The per-student "current course" the
 * signals key off is derived in memory from the passed-course set and matches
 * DashboardService::currentCourse exactly; AttentionTest re-derives every
 * fixture student's signals from the student-facing services and asserts the
 * two agree (the same no-duplicate-formula defense as US-607).
 *
 * Days are counted at calendar-day granularity: an event fires its signal
 * when its day is at least the threshold's number of days before today
 * (start-of-day comparison), so the boundary is exact and free of sub-second
 * drift.
 */
class AttentionService
{
    /**
     * Primary-reason priority, highest first. boss_fail is the current-course
     * gate state, so it leads; plain inactivity ranks below never_started
     * because an untouched account is a clearer outreach case than a student
     * who engaged once and stopped.
     */
    public const SIGNAL_PRIORITY = [
        'boss_fail',
        'repeat_fail',
        'low_performance',
        'stalled',
        'never_started',
        'inactive',
    ];

    /**
     * Repeated-failed-challenges threshold. Two, not three: one failure is a
     * single data point and a retry is a designed affordance (US-409); a
     * second, separate failure is the smallest evidence the first attempt's
     * feedback did not convert.
     */
    public const REPEAT_FAIL_THRESHOLD = 2;

    /**
     * Minimum scored attempts before low performance means anything. One
     * scored attempt is a data point, not a pattern.
     */
    public const LOW_PERFORMANCE_MIN_ATTEMPTS = 2;

    /**
     * Low-performance bar as a percent of the assessment's passing_score.
     * Averaging below 60% of the pass bar is scoring far beneath what the
     * challenge expects, not a barely-missed pass.
     */
    public const LOW_PERFORMANCE_PERCENT_OF_PASSING = 60;

    /**
     * Stall cut: no new mission completion on the current course for this
     * many days. Missions take minutes, so two full weeks without one more
     * completion on a course already started is halted work. Deliberately
     * shorter than INACTIVITY_DAYS so "dropped a course" and "gone quiet
     * entirely" stay distinguishable.
     */
    public const STALL_DAYS = 14;

    /**
     * Inactivity cut: no engine event from any source for this many days.
     * Three weeks spans a two-week cycle plus a week; quiet that long is not
     * a short break. Deliberately longer than STALL_DAYS.
     */
    public const INACTIVITY_DAYS = 21;

    /**
     * Never-started cut: account older than this with zero progress rows
     * anywhere. Two weeks is the grace for a new student to finish their
     * first mission.
     */
    public const NEVER_STARTED_DAYS = 14;

    /**
     * Display labels, keyed by signal. Indexes align with SIGNAL_PRIORITY.
     *
     * @var array<string, string>
     */
    public const SIGNAL_LABELS = [
        'boss_fail' => 'BOSS CHALLENGE FAILED',
        'repeat_fail' => 'REPEATED FAILURES',
        'low_performance' => 'LOW PERFORMANCE',
        'stalled' => 'STALLED',
        'never_started' => 'NOT STARTED',
        'inactive' => 'INACTIVE',
    ];

    /**
     * @param  Collection<int, int>|null  $studentIds  restrict the enumerated students (null = whole fleet; an empty set = nobody)
     * @param  array<int, Collection<int, int>>|null  $allowedByStudent  per-student monitorable course ids for a scoped teacher (null = unrestricted per student)
     * @return Collection<int, array{
     *     student: User,
     *     current_course: Course,
     *     signals: non-empty-array<int, string>,
     *     primary: string,
     *     reasons: array{}|array{boss_fail?: string, repeat_fail?: string, low_performance?: string, stalled?: string, inactive?: string, never_started?: string},
     *     last_activity_at: Carbon|null,
     * }>
     */
    public function list(?Collection $studentIds = null, ?array $allowedByStudent = null): Collection
    {
        $students = User::query()
            ->where('role', 'student')
            ->when($studentIds !== null, fn ($query) => $query->whereIn('id', $studentIds))
            ->orderBy('username')
            ->get();

        $courses = Course::query()->where('status', 'active')->orderBy('order_num')->orderBy('id')->get();

        $missionCounts = Mission::query()
            ->toBase()
            ->selectRaw('course_id, COUNT(*) as total')
            ->groupBy('course_id')
            ->pluck('total', 'course_id');

        $assessmentsByCourse = Assessment::query()
            ->get(['id', 'course_id', 'status', 'passing_score'])
            ->keyBy('course_id');

        $passedCoursesByUser = DB::table('the404_assessment_attempts as at')
            ->join('the404_assessments as a', 'a.id', '=', 'at.assessment_id')
            ->select('at.user_id as user_id', 'a.course_id as course_id')
            ->where('at.status', 'passed')
            ->distinct()
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $rows): Collection => $rows->pluck('course_id'));

        $progressByUserCourse = DB::table('the404_progress as p')
            ->join('the404_missions as m', 'm.id', '=', 'p.mission_id')
            ->selectRaw(
                'p.user_id as user_id, m.course_id as course_id, '
                .'COUNT(DISTINCT p.mission_id) as done, MAX(p.completed_at) as completed_at',
            )
            ->groupBy('p.user_id', 'm.course_id')
            ->get()
            ->groupBy('user_id');

        $attemptsByUserCourse = DB::table('the404_assessment_attempts as at')
            ->join('the404_assessments as a', 'a.id', '=', 'at.assessment_id')
            ->select('at.id as id', 'at.user_id as user_id', 'a.course_id as course_id', 'at.status as status', 'at.score as score')
            ->get()
            ->groupBy('user_id');

        $lastEventsByUser = $this->lastEventsByUser();

        $hasCoursesToStart = $courses->contains(
            fn (Course $course): bool => ((int) ($missionCounts->get($course->id) ?? 0)) > 0,
        );

        $rows = [];

        foreach ($students as $student) {
            $passedSet = $passedCoursesByUser->get($student->id, collect());
            $progressRows = $progressByUserCourse->get($student->id, collect());
            $attemptRows = $attemptsByUserCourse->get($student->id, collect());

            $allowed = $allowedByStudent[$student->id] ?? null;
            $candidateCourses = $allowed === null
                ? $courses
                : $courses->filter(fn (Course $course): bool => $allowed->contains($course->id))->values();

            $current = $this->currentCourse($candidateCourses, $missionCounts, $passedSet);

            if ($current === null) {
                continue;
            }

            $assessment = $assessmentsByCourse->get($current->id);

            $progress = $progressRows->first(fn (object $row): bool => (int) $row->course_id === $current->id);
            $done = (int) ($progress->done ?? 0);
            $total = (int) ($missionCounts->get($current->id) ?? 0);
            $lastCompletion = isset($progress->completed_at) ? Carbon::parse($progress->completed_at) : null;

            $attempts = $attemptRows
                ->filter(fn (object $row): bool => (int) $row->course_id === $current->id)
                ->sortBy('id')
                ->values();
            $failedCount = $attempts->filter(fn (object $row): bool => $row->status === 'failed')->count();
            $latest = $attempts->last();
            $scored = $attempts->filter(
                fn (object $row): bool => in_array($row->status, ['passed', 'failed'], true) && $row->score !== null,
            );
            $scoredCount = $scored->count();
            $totalScore = (int) $scored->sum(fn (object $row): int => (int) $row->score);

            $lastEvent = $this->lastEventAt($lastEventsByUser, $student->id);
            $passingScore = (int) ($assessment->passing_score ?? 0);

            $signals = [];
            $reasons = [];

            if ($assessment !== null && $latest !== null && $latest->status === 'failed') {
                $signals[] = 'boss_fail';
                $reasons['boss_fail'] = sprintf(
                    'Boss Challenge failed (score %d/%d, attempt %d)',
                    (int) $latest->score,
                    $passingScore,
                    $attempts->search(fn (object $row): bool => (int) $row->id === (int) $latest->id) + 1,
                );
            }

            if ($failedCount >= self::REPEAT_FAIL_THRESHOLD) {
                $signals[] = 'repeat_fail';
                $reasons['repeat_fail'] = sprintf('Failed the %s challenge %d times', $current->name, $failedCount);
            }

            if ($assessment !== null
                && $passingScore > 0
                && $scoredCount >= self::LOW_PERFORMANCE_MIN_ATTEMPTS
                && $totalScore * 100 < $passingScore * self::LOW_PERFORMANCE_PERCENT_OF_PASSING * $scoredCount
            ) {
                $signals[] = 'low_performance';
                $reasons['low_performance'] = sprintf(
                    'Average score %d/%d across %d attempts',
                    (int) round($totalScore / $scoredCount),
                    $passingScore,
                    $scoredCount,
                );
            }

            if ($done > 0
                && $done < $total
                && $lastCompletion !== null
                && $this->daysSince($lastCompletion) >= self::STALL_DAYS
            ) {
                $signals[] = 'stalled';
                $reasons['stalled'] = sprintf(
                    'No new mission on %s in %d days (%d/%d missions)',
                    $current->name,
                    $this->daysSince($lastCompletion),
                    $done,
                    $total,
                );
            }

            if ($lastEvent !== null && $this->daysSince($lastEvent) >= self::INACTIVITY_DAYS) {
                $signals[] = 'inactive';
                $reasons['inactive'] = sprintf('No activity in %d days', $this->daysSince($lastEvent));
            }

            $enrolmentDays = $this->daysSince($student->created_at);

            if ($hasCoursesToStart && $progressRows->isEmpty() && $enrolmentDays >= self::NEVER_STARTED_DAYS) {
                $signals[] = 'never_started';
                $reasons['never_started'] = sprintf('No progress since enrolment %d days ago', $enrolmentDays);
            }

            $orderedSignals = collect(self::SIGNAL_PRIORITY)
                ->filter(fn (string $signal): bool => in_array($signal, $signals, true))
                ->values()
                ->all();

            if ($orderedSignals === []) {
                continue;
            }

            $rows[] = [
                'student' => $student,
                'current_course' => $current,
                'signals' => $orderedSignals,
                'primary' => $orderedSignals[0],
                'reasons' => $reasons,
                'last_activity_at' => $lastEvent,
            ];
        }

        usort($rows, function (array $a, array $b): int {
            $priorityDiff = $this->priorityOf($a['primary']) <=> $this->priorityOf($b['primary']);

            if ($priorityDiff !== 0) {
                return $priorityDiff;
            }

            $lastA = $a['last_activity_at'] !== null ? $a['last_activity_at']->getTimestamp() : 0;
            $lastB = $b['last_activity_at'] !== null ? $b['last_activity_at']->getTimestamp() : 0;

            return $lastA <=> $lastB;
        });

        return collect($rows);
    }

    /**
     * The first active course, in progression order, with at least one
     * mission the student has not passed. Matches DashboardService::currentCourse:
     * completion reads the passed-attempt history, a zero-mission course is
     * skipped, and locked/draft courses are already excluded by $courses.
     *
     * @param  Collection<int, Course>  $courses
     * @param  Collection<int, int>  $missionCounts
     * @param  Collection<int, int>  $passedSet
     */
    private function currentCourse(Collection $courses, Collection $missionCounts, Collection $passedSet): ?Course
    {
        return $courses->first(function (Course $course) use ($missionCounts, $passedSet): bool {
            if (((int) ($missionCounts->get($course->id) ?? 0)) === 0) {
                return false;
            }

            return ! $passedSet->contains($course->id);
        });
    }

    /**
     * @return array<string, Collection<int, int|string>>
     */
    private function lastEventsByUser(): array
    {
        return [
            'progress' => DB::table('the404_progress')
                ->selectRaw('user_id, MAX(completed_at) as last')
                ->groupBy('user_id')
                ->pluck('last', 'user_id'),
            'attempts' => DB::table('the404_assessment_attempts')
                ->selectRaw('user_id, MAX(COALESCE(passed_at, submitted_at, created_at)) as last')
                ->groupBy('user_id')
                ->pluck('last', 'user_id'),
            'activity' => DB::table('the404_activity')
                ->selectRaw('user_id, MAX(created_at) as last')
                ->groupBy('user_id')
                ->pluck('last', 'user_id'),
            'xp' => DB::table('the404_xp_transactions')
                ->selectRaw('user_id, MAX(created_at) as last')
                ->groupBy('user_id')
                ->pluck('last', 'user_id'),
        ];
    }

    /**
     * @param  array<string, Collection<int, int|string>>  $sources
     */
    private function lastEventAt(array $sources, int $userId): ?Carbon
    {
        $last = null;

        foreach ($sources as $source) {
            $value = $source->get($userId);

            if ($value === null) {
                continue;
            }

            $candidate = Carbon::parse($value);

            if ($last === null || $candidate->greaterThan($last)) {
                $last = $candidate;
            }
        }

        return $last;
    }

    /**
     * Whole calendar days between a timestamp's day and today's day. Both
     * sides are snapped to start-of-day, so this is exact and repeatable no
     * matter what time of day the comparison happens. Carbon's diffInDays is
     * signed in Carbon 3, so the absolute value is taken.
     */
    private function daysSince(CarbonInterface $at): int
    {
        return abs((int) now()->startOfDay()->diffInDays($at->copy()->startOfDay()));
    }

    /**
     * 0-based priority of a signal key; absent keys sort last.
     */
    private function priorityOf(string $signal): int
    {
        $priority = array_search($signal, self::SIGNAL_PRIORITY, true);

        return $priority === false ? PHP_INT_MAX : $priority;
    }
}
