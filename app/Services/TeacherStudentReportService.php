<?php

namespace App\Services;

use App\Models\User;
use App\Support\ReportFilters;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Teacher student report (US-1003). The teacher-facing descriptive report
 * for one authorized student, confined to the teacher's active
 * classroom/course relationships. The report composes the accepted student
 * progress report under the authorized scope and exposes only the
 * teacher-visible subset — progress, challenges, assessments, competency,
 * recommendations, and period movement. XP balance, achievements, streak,
 * and timeline stay student-private: the existing accepted teacher surface
 * (progress, assessment performance, competency, recommendations) never
 * carried them, and this report does not broaden it.
 *
 * Authorization contract:
 * - the teacher must satisfy
 *   ReportAuthorizationService::canViewStudentReport() for the target, which
 *   requires the target to be a student enrolled at one of the teacher's
 *   ACTIVE classrooms. Anything else throws AuthorizationException before
 *   any report data is composed — never compose-then-authorize.
 * - the course scope comes only from
 *   ReportAuthorizationService::reportCourseIds(), never from
 *   client-provided ids. For teachers this is exactly the ACTIVE shared
 *   courses; an empty set matches nothing, never the whole catalog, and
 *   null is never treated as teacher fleet access.
 * - no classroom, role, or enrollment logic lives here. Both verdicts
 *   delegate to the accepted authorization contract.
 *
 * Filter contract (inherited from the composed student report):
 * - the requested student must equal the authorized target or the whole
 *   report fails closed to neutral; the subject is never switched.
 * - the requested course intersects the teacher-authorized scope; an
 *   out-of-scope course fails the course-relative sections closed.
 * - date narrows challenges/assessments/period only; timeline is absent
 *   and recommendations stay current by owner design.
 * - no status vocabulary is registered, so validated filters can never
 *   carry one. Filters can only narrow authorization, never broaden it.
 */
class TeacherStudentReportService
{
    public function __construct(
        private readonly ReportAuthorizationService $authorization,
        private readonly StudentProgressReportService $studentReport,
    ) {}

    /**
     * @return array{
     *     teacher_id: int,
     *     student_id: int,
     *     course_ids: list<int>,
     *     progress: array<string, mixed>,
     *     challenges: array<string, mixed>,
     *     assessments: array<string, mixed>,
     *     competency: array<string, mixed>,
     *     recommendations: list<array{slot: int, title: string, subtitle: string, href: string, cta: string}>,
     *     period: array<string, mixed>,
     * }
     *
     * @throws AuthorizationException
     */
    public function forTeacherStudent(
        User $teacher,
        User $student,
        ?ReportFilters $filters = null,
    ): array {
        if (! $this->authorization->canViewStudentReport($teacher, $student)) {
            throw new AuthorizationException('Not authorized to view this student report.');
        }

        $scope = $this->authorization->reportCourseIds($teacher, $student);

        if ($scope === null) {
            throw new AuthorizationException('Not authorized to view this student report.');
        }

        $report = $this->studentReport->forStudent($student, $scope, $filters);

        return [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'course_ids' => array_values($scope->all()),
            'progress' => $report['progress'],
            'challenges' => $report['challenges'],
            'assessments' => $report['assessments'],
            'competency' => $report['competency'],
            'recommendations' => $report['recommendations'],
            'period' => $report['period'],
        ];
    }
}
