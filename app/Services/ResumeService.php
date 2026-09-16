<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Section;
use App\Models\User;

/**
 * Resolves where "Continue Learning" takes the student (US-504).
 *
 * §14.0 priority, strictest useful scope first:
 *   1. unfinished lesson/challenge -> the first unfinished mission in the
 *      current course, in course order, drafts preferred (exactly what
 *      DashboardService::nextMission() derives, the same traversal the
 *      learning path uses). In-progress work is never skipped for a later,
 *      untouched mission.
 *   2. current section -> a mission exists iff some section still has
 *      unfinished work, so nextMission() already lands within the right
 *      section; that section rides along as the position's context.
 *   3. current course -> every mission is complete but the Boss Challenge is
 *      still outstanding (the US-410/US-414 edge). The student resumes at
 *      course level. The destination is the learning surface, NEVER the
 *      assessment.
 *
 * Guarantees (never): a random challenge (always the derived position), a
 * jump straight to an assessment (course-level resume routes to the learning
 * path, not assessment.show), or ignoring incomplete work (the first
 * unfinished mission wins).
 *
 * Returns null only when every course has had its Boss Challenge passed,
 * which drives the dashboard all-clear state.
 */
class ResumeService
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    /**
     * @return null|array{
     *     type: 'mission'|'course',
     *     course: Course,
     *     section?: Section|null,
     *     mission?: Mission,
     * }
     */
    public function resolve(User $user): ?array
    {
        $course = $this->dashboard->currentCourse($user);

        if ($course === null) {
            return null;
        }

        $mission = $this->dashboard->nextMission($user, $course);

        if ($mission !== null) {
            return [
                'type' => 'mission',
                'course' => $course,
                'section' => $mission->section,
                'mission' => $mission,
            ];
        }

        return [
            'type' => 'course',
            'course' => $course,
        ];
    }
}
