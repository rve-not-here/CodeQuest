<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly XpService $xp,
        private readonly AssessmentService $assessments,
        private readonly AchievementService $achievements,
    ) {}

    /**
     * The first active course, in progression order, whose Boss Challenge the
     * user has not yet passed (US-410).
     *
     * "Course complete" is defined by the assessment gate (§4): a student who
     * has EVER passed the course's assessment has completed the course, even
     * if they later open a retry attempt (US-409) that fails — completion
     * reads attempt history via AssessmentService::hasPassed(), never the
     * latest verdict, so a retry can never un-complete a course or re-lock
     * the next one. Finishing every mission is not completion by itself; it
     * only unlocks the Boss Challenge. A course with no missions is skipped:
     * it can never unlock a challenge and would otherwise strand the student
     * here forever.
     *
     * An optional $courseIds scope restricts the search to a monitorable
     * course set (a teacher's shared classrooms). null keeps the whole active
     * catalog; an empty Collection yields no current course.
     *
     * @param  Collection<int, int>|null  $courseIds
     */
    public function currentCourse(User $user, ?Collection $courseIds = null): ?Course
    {
        $courses = Course::query()
            ->where('status', 'active')
            ->orderBy('order_num')
            ->withCount('missions')
            ->when($courseIds !== null, fn ($query) => $query->whereIn('id', $courseIds))
            ->get();

        if ($courses->isEmpty()) {
            return null;
        }

        // US-911: one batched pass-history read instead of hasPassed() per
        // course. The verdict per course is identical: skipped when the
        // course has no missions, otherwise current while no passed attempt
        // exists for its assessment.
        $assessmentIds = $this->assessments->assessmentIdsByCourse($courses->pluck('id'));
        $passed = $this->assessments->passedAssessmentIds($user);

        return $courses->first(function (Course $course) use ($assessmentIds, $passed): bool {
            if ($course->missions_count === 0) {
                return false;
            }

            $assessmentId = $assessmentIds->get($course->id);

            return $assessmentId === null || ! $passed->contains($assessmentId);
        });
    }

    /**
     * The next mission the user has not yet completed, in course order.
     */
    public function nextMission(User $user, Course $course): ?Mission
    {
        $completed = $user->progress()
            ->whereIn('mission_id', $course->missions()->select('id'))
            ->pluck('mission_id')
            ->all();

        return $course->missions()
            ->whereNotIn('id', $completed)
            ->orderBy('order_num')
            ->first();
    }

    /**
     * @return array{completed: int, total: int, percent: int}
     */
    public function courseProgress(User $user, Course $course): array
    {
        $total = $course->missions()->count();
        $completed = $this->completedMissionCount($user, $course);

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $this->percent($completed, $total),
        ];
    }

    /**
     * Progress map for a user across a set of courses in a single query
     * (US-911). Returns course_id => array{completed, total, percent}.
     *
     * @param  Collection<int, Course>  $courses
     * @return Collection<int, array{completed: int, total: int, percent: int}>
     */
    public function courseProgressMap(User $user, Collection $courses): Collection
    {
        if ($courses->isEmpty()) {
            return collect();
        }

        $courseIds = $courses->pluck('id');

        $missionIdsByCourse = Mission::query()
            ->whereIn('course_id', $courseIds)
            ->get(['id', 'course_id']);

        $totalByCourse = $missionIdsByCourse->groupBy('course_id')->map->count();

        $completedMissionIds = Progress::query()
            ->where('user_id', $user->id)
            ->whereIn('mission_id', $missionIdsByCourse->pluck('id'))
            ->pluck('mission_id')
            ->all();

        $completedByCourse = $missionIdsByCourse
            ->filter(fn ($mission) => in_array($mission->id, $completedMissionIds, true))
            ->groupBy('course_id')
            ->map->count();

        return $courses->mapWithKeys(function (Course $course) use ($totalByCourse, $completedByCourse): array {
            $total = $totalByCourse->get($course->id, 0);
            $completed = $completedByCourse->get($course->id, 0);

            return [$course->id => [
                'completed' => $completed,
                'total' => $total,
                'percent' => $this->percent($completed, $total),
            ]];
        });
    }

    public function totalXp(User $user): int
    {
        return $this->xp->balance($user);
    }

    /** @return Collection<int, Activity> */
    public function recentActivity(User $user): Collection
    {
        return $user->activities()
            ->latest('created_at')
            ->limit(6)
            ->get();
    }

    /**
     * Real learner-derived counters for the Systems panel's replacement
     * (dashboard §systems). Every number is computed from actual records owned
     * by the caller: XP via XpService::balance(), achievements earned via
     * AchievementService::catalog(), the learning-day streak via
     * AchievementService::currentStreak(), completed missions from the
     * Progress ledger, and passed boss challenges via AssessmentService.
     *
     * @return array{
     *     total_xp: int,
     *     achievements: int,
     *     streak: int,
     *     missions_completed: int,
     *     boss_challenges_passed: int,
     * }
     */
    public function learnerStats(User $user): array
    {
        return [
            'total_xp' => $this->xp->balance($user),
            'achievements' => $this->achievements->catalog($user)
                ->filter(fn (array $row): bool => $row['awarded'])
                ->count(),
            'streak' => $this->achievements->currentStreak($user),
            'missions_completed' => (int) Progress::query()
                ->where('user_id', $user->id)
                ->count(),
            'boss_challenges_passed' => $this->assessments->passedCount($user),
        ];
    }

    /**
     * Assessment readiness as a percentage of missions completed in a course.
     */
    public function assessmentReadiness(int $completed, int $total): int
    {
        return $this->percent($completed, $total);
    }

    private function completedMissionCount(User $user, Course $course): int
    {
        return (int) Progress::query()
            ->where('user_id', $user->id)
            ->whereIn('mission_id', $course->missions()->select('id'))
            ->count();
    }

    private function percent(int $completed, int $total): int
    {
        return $total > 0 ? (int) round(($completed / $total) * 100) : 0;
    }
}
