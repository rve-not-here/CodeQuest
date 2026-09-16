<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds the learning-path view model: courses with their sections and
 * missions, each tagged with the student's progress state, and resolves
 * the next mission to navigate to.
 *
 * Mission states are derived, never stored:
 *   COMPLETED      -> a Progress row exists
 *   IN PROGRESS    -> a draft exists (and no completion)
 *   NOT STARTED    -> neither
 */
class LearningPathService
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly DraftService $drafts,
    ) {}

    /**
     * @return Collection<int, array{
     *     course: Course,
     *     progress: array{completed: int, total: int, percent: int},
     *     sections: Collection<int, array{section: Section, progress: array{completed: int, total: int, percent: int}, missions: Collection<int, array{mission: Mission, state: string, xp: int}>}>
     * }>
     */
    public function build(User $user): Collection
    {
        return Course::query()
            ->orderBy('order_num')
            ->with(['sections.missions', 'missions'])
            ->get()
            ->map(function (Course $course) use ($user): array {
                $completedMissionIds = $this->completedMissionIds($user, $course);
                $draftMissionIds = $this->draftMissionIds($user, $course);

                $sections = $course->sections->map(function ($section) use ($completedMissionIds, $draftMissionIds): array {
                    $missions = $section->missions
                        ->sortBy('order_num')
                        ->map(function (Mission $mission) use ($completedMissionIds, $draftMissionIds): array {
                            return $this->missionView($mission, $completedMissionIds, $draftMissionIds);
                        })
                        ->values();

                    return [
                        'section' => $section,
                        'progress' => $this->sectionProgress($missions),
                        'missions' => $missions,
                    ];
                });

                return [
                    'course' => $course,
                    'progress' => $this->courseProgress($user, $course),
                    'sections' => $sections,
                ];
            });
    }

    /**
     * The mission the student should land on next: the first mission in
     * course order that is not completed, preferring an in-progress draft.
     */
    public function nextMission(User $user, ?Course $course = null): ?Mission
    {
        $course ??= $this->dashboard->currentCourse($user);

        if ($course === null) {
            return null;
        }

        $missions = $course->missions()
            ->orderBy('order_num')
            ->with('section')
            ->get();

        $completed = $this->completedMissionIds($user, $course);
        $drafts = $this->draftMissionIds($user, $course);

        foreach ($drafts as $id) {
            if (! in_array($id, $completed, true)) {
                $mission = $missions->firstWhere('id', $id);

                if ($mission !== null) {
                    return $mission;
                }
            }
        }

        foreach ($missions as $mission) {
            if (! in_array($mission->id, $completed, true)) {
                return $mission;
            }
        }

        return null;
    }

    /**
     * @return array{mission: Mission, state: string}
     */
    private function missionView(Mission $mission, array $completedMissionIds, array $draftMissionIds): array
    {
        $state = in_array($mission->id, $completedMissionIds, true)
            ? 'COMPLETED'
            : (in_array($mission->id, $draftMissionIds, true) ? 'IN PROGRESS' : 'NOT STARTED');

        return [
            'mission' => $mission,
            'state' => $state,
        ];
    }

    /**
     * @param  Collection<int, array{mission: Mission, state: string}>  $missions
     * @return array{completed: int, total: int, percent: int}
     */
    private function sectionProgress(Collection $missions): array
    {
        $completed = $missions->filter(fn (array $m): bool => $m['state'] === 'COMPLETED')->count();
        $total = $missions->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
        ];
    }

    /**
     * @return array{completed: int, total: int, percent: int}
     */
    private function courseProgress(User $user, Course $course): array
    {
        $missions = $course->missions()->count();
        $completed = count($this->completedMissionIds($user, $course));

        return [
            'completed' => $completed,
            'total' => $missions,
            'percent' => $missions > 0 ? (int) round(($completed / $missions) * 100) : 0,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function completedMissionIds(User $user, Course $course): array
    {
        return $user->progress()
            ->whereIn('mission_id', $course->missions()->select('id'))
            ->pluck('mission_id')
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function draftMissionIds(User $user, Course $course): array
    {
        return $user->missionDrafts()
            ->whereIn('mission_id', $course->missions()->select('id'))
            ->pluck('mission_id')
            ->all();
    }
}
