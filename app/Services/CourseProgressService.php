<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds the course-progress overview: every course with its mission progress
 * and a status label, all derived server-side from persisted data (US-502).
 *
 * Nothing here is hard-coded or client-supplied. Mission counts come from
 * DashboardService::courseProgress() (real Progress rows), completion reuses
 * the Phase 4 mechanism AssessmentService::hasPassed() (a passed Boss
 * Challenge attempt), unlocking reuses AssessmentService::isUnlocked(), and
 * the "current" course reuses DashboardService::currentCourse(). There is no
 * second completion path.
 *
 * State labels, in precedence order:
 *   LOCKED      -> course record is not active, or the course sits ahead of
 *                  the student in the progression and is not reached yet
 *   COMPLETED   -> hasPassed() (the assessment gate; a later failed retry
 *                  never un-completes a course)
 *   READY       -> assessment unlocked (all missions done, challenge active)
 *   IN PROGRESS -> the derived current course, missions still due
 */
class CourseProgressService
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly AssessmentService $assessments,
    ) {}

    /**
     * An optional $courseIds scope restricts the overview to a monitorable
     * course set (a teacher's shared classrooms). null keeps every course; an
     * empty Collection yields no rows.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, array{
     *     course: Course,
     *     progress: array{completed: int, total: int, percent: int},
     *     state: string,
     * }>
     */
    public function overview(User $user, ?Collection $courseIds = null): Collection
    {
        $currentCourseId = $this->dashboard
            ->currentCourse($user, $courseIds)?->id;

        $courses = Course::query()
            ->orderBy('order_num')
            ->when($courseIds !== null, fn ($query) => $query->whereIn('id', $courseIds))
            ->get();

        if ($courses->isEmpty()) {
            return collect();
        }

        // US-911: progress and assessment state for every course in grouped
        // reads instead of courseProgress/hasPassed/isUnlocked per course.
        // Labels keep the exact precedence below.
        $progress = $this->dashboard->courseProgressMap($user, $courses);
        $states = $this->assessments->assessmentStatesForCourses($user, $courses);

        return $courses->map(function (Course $course) use ($currentCourseId, $progress, $states): array {
            // The map covers every input course; the default matches
            // zero-mission math and only satisfies the type.
            $courseProgress = $progress->get($course->id) ?? ['completed' => 0, 'total' => 0, 'percent' => 0];

            return [
                'course' => $course,
                'progress' => $courseProgress,
                'state' => $this->stateFor($course, $states[$course->id], $currentCourseId),
            ];
        });
    }

    /**
     * @param  array{assessment: ?Assessment, passed: bool, eligible: bool}  $state
     */
    private function stateFor(Course $course, array $state, ?int $currentCourseId): string
    {
        if ($course->status !== 'active') {
            return 'LOCKED';
        }

        if ($state['passed']) {
            return 'COMPLETED';
        }

        if ($state['assessment'] !== null && $state['assessment']->status === 'active' && $state['eligible']) {
            return 'READY';
        }

        if ($course->id === $currentCourseId) {
            return 'IN PROGRESS';
        }

        return 'LOCKED';
    }
}
