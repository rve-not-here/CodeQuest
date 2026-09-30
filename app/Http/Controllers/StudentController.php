<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentOverviewRequest;
use App\Models\User;
use App\Services\AttentionNotificationService;
use App\Services\ClassroomAccessService;
use App\Services\StudentService;
use App\Services\TeacherDashboardService;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentService $students,
        private readonly TeacherDashboardService $dashboard,
        private readonly AttentionNotificationService $attentionNotifications,
        private readonly ClassroomAccessService $access,
    ) {}

    /**
     * The student overview (US-602), which also carries the teacher dashboard
     * (US-609): /students is where AuthController::homeFor lands teachers after
     * login, and a separate summary page would only duplicate the roster it
     * must show, so the summary composes the existing services and renders
     * above the same filtered roster. No student identifier is accepted, so a
     * request that tries to pivot the list onto a specific user fails loudly
     * (403) rather than silently being ignored.
     *
     * Teacher visibility flows through ClassroomAccessService: an admin is
     * fleet-wide, a teacher monitors only the students and courses of their
     * ACTIVE classrooms (course-level: a student with shared classrooms is
     * scoped to exactly the courses shared with this teacher).
     */
    public function __invoke(StudentOverviewRequest $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'Student overview takes no student identifier.');
        }

        $filters = $request->safe(['q', 'course', 'status']);

        $paginatorQuery = array_filter($filters, fn ($value): bool => $value !== null);

        /** @var User $user */
        $user = auth()->user();

        $scope = $this->access->scopesFor($user);

        $rows = $this->students->index(
            $filters['q'] ?? null,
            isset($filters['course']) ? (int) $filters['course'] : null,
            $filters['status'] ?? null,
            $paginatorQuery,
            $scope['byStudent'],
        );

        $overview = $this->dashboard->overview(
            8,
            $scope['studentIds'],
            $scope['courseIds'],
            $scope['byStudent'],
        );

        // US-806: the teacher's notification center is synced lazily from the
        // needs-attention list whenever they open the dashboard. The sync is
        // the one deliberate write exception inside this teacher-area handler
        // (see .ai/rules/controllers.md); it runs only after the IDOR guard
        // and after the page data is composed.
        $this->attentionNotifications->syncFor($user);

        return view('students', [
            'role' => $user->role,
            'students' => $rows,
            'filterCourses' => $this->students->courseOptions($scope['courseIds']),
            'filters' => $filters,
            'dashboardMetrics' => $overview['metrics'],
            'dashboardActivity' => $overview['recent_activity'],
            'dashboardSummary' => $overview['assessment_summary'],
        ]);
    }
}
