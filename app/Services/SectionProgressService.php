<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds the section-progress overview (US-503): each section's mission
 * progress and a server-derived state label, grouped under its course.
 *
 * Derivation reuses the Phase 3/4 traversal in LearningPathService::build()
 * (real course->section->mission hierarchy, per-mission Progress rows) — no
 * independent aggregation. A section's counts come from that section's own
 * missions; the flat "repeated course-level percentage" is never used. The
 * per-course counts are likewise whatever build() already derives.
 *
 * Section state labels:
 *   EMPTY       -> section has no missions (vacuously complete, contributes
 *                  nothing — never masks another section's real state)
 *   DONE        -> every mission in the section completed
 *   IN PROGRESS -> some (not all) missions completed
 *   NOT STARTED -> no missions completed yet
 */
class SectionProgressService
{
    public function __construct(
        private readonly LearningPathService $path,
    ) {}

    /**
     * @return Collection<int, array{
     *     course: Course,
     *     progress: array{completed: int, total: int, percent: int},
     *     sections: Collection<int, array{
     *         section: Section,
     *         progress: array{completed: int, total: int, percent: int},
     *         state: string,
     *     }>,
     * }>
     */
    public function overview(User $user): Collection
    {
        return $this->path->build($user)->map(function (array $courseRow): array {
            $sections = $courseRow['sections']->map(function (array $sectionRow): array {
                return [
                    'section' => $sectionRow['section'],
                    'progress' => $sectionRow['progress'],
                    'state' => $this->stateFor($sectionRow['progress']),
                ];
            });

            return [
                'course' => $courseRow['course'],
                'progress' => $courseRow['progress'],
                'sections' => $sections,
            ];
        });
    }

    /**
     * @param  array{completed: int, total: int, percent: int}  $progress
     */
    private function stateFor(array $progress): string
    {
        if ($progress['total'] === 0) {
            return 'EMPTY';
        }

        if ($progress['percent'] === 100) {
            return 'DONE';
        }

        if ($progress['completed'] > 0) {
            return 'IN PROGRESS';
        }

        return 'NOT STARTED';
    }
}
