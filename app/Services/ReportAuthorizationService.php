<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The single reusable authorization contract for report access (US-1001).
 *
 * Thin and compositional: every verdict delegates to the existing
 * primitives — UserPolicy::view for per-student inspection and
 * ClassroomAccessService for classroom/course scope — so no second
 * ownership or classroom system exists. Unknown roles fail closed because
 * none of the underlying checks pass for them.
 *
 * Default behavior is DENY. Controllers must abort with 403 on false;
 * authorization always runs before report data composition. Future CSV/PDF
 * exports (US-1010) reuse these exact methods against the same models, so
 * an export can never see more than its matching on-screen report.
 */
class ReportAuthorizationService
{
    public function __construct(
        private readonly ClassroomAccessService $access,
    ) {}

    /**
     * Who may open a per-student report: a student viewing their own rows,
     * or anyone UserPolicy::view already permits to inspect that student's
     * learning data (classroom-authorized teachers, fleet-wide admins).
     *
     * Self-identity alone grants nothing: the self branch additionally
     * requires the student role, so operators and unknown roles cannot
     * inherit student self access. UserPolicy::view is deliberately NOT
     * consulted for the self case because its bare identity check is wider
     * than the report contract allows.
     */
    public function canViewStudentReport(User $viewer, User $student): bool
    {
        if ($viewer->id === $student->id) {
            return $viewer->role === 'student';
        }

        return $viewer->can('view', $student);
    }

    /**
     * The course ids a per-student report may include. null means no course
     * restriction — for student self-reports (row queries stay bound to the
     * student's own id) and for admins (fleet-wide by definition). Teachers
     * receive exactly the courses shared with the student through ACTIVE
     * classrooms; an empty set matches nothing, never the whole catalog.
     * Unsupported roles receive an empty set even for themselves, so null
     * never represents an unauthorized caller.
     *
     * @return Collection<int, int>|null
     */
    public function reportCourseIds(User $viewer, User $student): ?Collection
    {
        if ($viewer->id === $student->id) {
            return $viewer->role === 'student' ? null : collect();
        }

        return $this->access->courseIdsForStudent($viewer, $student);
    }

    /**
     * Who may open a course-level report: fleet-wide admins, or teachers
     * whose ACTIVE classrooms include the course. Students never receive
     * course-wide aggregates of other learners, not even for courses they
     * take themselves.
     */
    public function canViewCourseReport(User $viewer, Course $course): bool
    {
        if ($viewer->role === 'student') {
            return false;
        }

        return $this->access->isAuthorizedForCourse($viewer, $course);
    }

    /**
     * Who may open a system-wide report: fleet-wide admins only. Teachers,
     * students, and operators are refused regardless of classroom state.
     */
    public function canViewSystemReport(User $viewer): bool
    {
        return $this->access->isFleetWide($viewer);
    }
}
