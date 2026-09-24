<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\Progress;
use App\Models\Section;
use App\Models\User;
use Carbon\Carbon;
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
        $courses = Course::query()
            ->orderBy('order_num')
            ->with(['sections.missions', 'missions'])
            ->get();

        // US-911: completion and draft evidence for the whole catalog in
        // two grouped reads instead of per-course lookups. State derivation
        // below is unchanged PHP over these sets.
        $allMissionIds = $courses->flatMap(fn (Course $course) => $course->missions->pluck('id'));

        $completed = Progress::query()
            ->where('user_id', $user->id)
            ->whereIn('mission_id', $allMissionIds)
            ->pluck('mission_id')
            ->flip();

        $drafted = MissionDraft::query()
            ->where('user_id', $user->id)
            ->whereIn('mission_id', $allMissionIds)
            ->pluck('mission_id')
            ->flip();

        return $courses->map(function (Course $course) use ($completed, $drafted): array {
            $completedIds = $course->missions
                ->pluck('id')
                ->filter(fn (int $id): bool => $completed->has($id))
                ->values()
                ->all();

            $draftIds = $course->missions
                ->pluck('id')
                ->filter(fn (int $id): bool => $drafted->has($id))
                ->values()
                ->all();

            $sections = $course->sections->map(function ($section) use ($completedIds, $draftIds): array {
                $missions = $section->missions
                    ->sortBy('order_num')
                    ->map(function (Mission $mission) use ($completedIds, $draftIds): array {
                        return $this->missionView($mission, $completedIds, $draftIds);
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
                'progress' => $this->courseProgress($completedIds, $course->missions->count()),
                'sections' => $sections,
            ];
        });
    }

    /**
     * Completed section beats for a set of students. Uses the same section
     * mission membership and all-missions-complete rule as build().
     *
     * @param  Collection<int, User>  $users
     * @param  array<int, Collection<int, int>>|null  $studentCourseScopes
     * @return Collection<int, array{user_id: int, section: Section, at: Carbon}>
     */
    public function completedSectionBeatsForUsers(Collection $users, ?array $studentCourseScopes = null): Collection
    {
        if ($users->isEmpty()) {
            return collect();
        }

        $courses = Course::query()
            ->orderBy('order_num')
            ->get(['id']);
        $sectionsByCourse = Section::query()
            ->whereIn('course_id', $courses->pluck('id'))
            ->orderBy('order_num')
            ->get(['id', 'course_id', 'title'])
            ->groupBy('course_id');
        $missionsBySection = Mission::query()
            ->whereIn('section_id', $sectionsByCourse->flatten()->pluck('id'))
            ->get(['id', 'section_id'])
            ->groupBy('section_id');
        $missionIds = $missionsBySection->flatten()->pluck('id');

        $progress = Progress::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->whereIn('mission_id', $missionIds)
            ->when($studentCourseScopes !== null, function ($query) use ($studentCourseScopes, $sectionsByCourse, $missionsBySection): void {
                $query->where(function ($query) use ($studentCourseScopes, $sectionsByCourse, $missionsBySection): void {
                    foreach ($studentCourseScopes ?? [] as $userId => $allowedCourses) {
                        $allowedSectionIds = collect($sectionsByCourse->all())->only($allowedCourses)->flatten()->pluck('id');
                        $allowedMissionIds = collect($missionsBySection->all())->only($allowedSectionIds)->flatten()->pluck('id');
                        $query->orWhere(fn ($query) => $query->where('user_id', $userId)->whereIn('mission_id', $allowedMissionIds));
                    }
                });
            })
            ->get(['user_id', 'mission_id', 'completed_at'])
            ->groupBy('user_id')
            ->map(fn (Collection $rows): Collection => $rows->keyBy('mission_id'));

        $beats = collect();

        foreach ($users as $user) {
            $completed = $progress->get($user->id) ?? collect();

            foreach ($courses as $course) {
                if ($studentCourseScopes !== null && ! ($studentCourseScopes[$user->id] ?? collect())->contains($course->id)) {
                    continue;
                }

                foreach ($sectionsByCourse->get($course->id, collect()) as $section) {
                    $missions = $missionsBySection->get($section->id, collect());

                    if ($missions->isEmpty()) {
                        continue;
                    }

                    $completions = $missions->map(fn (Mission $mission): ?Progress => $completed->get($mission->id));

                    if ($completions->filter()->count() !== $missions->count()) {
                        continue;
                    }

                    $latest = $completions->sortByDesc('completed_at')->first();

                    if ($latest === null) {
                        continue;
                    }

                    $beats->push([
                        'user_id' => $user->id,
                        'section' => $section,
                        'at' => Carbon::parse($latest->completed_at),
                    ]);
                }
            }
        }

        return $beats;
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
     * @param  array<int, int>  $completedIds
     * @return array{completed: int, total: int, percent: int}
     */
    private function courseProgress(array $completedIds, int $total): array
    {
        $completed = count($completedIds);

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
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
