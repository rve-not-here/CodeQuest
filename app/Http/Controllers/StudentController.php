<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentOverviewRequest;
use App\Models\User;
use App\Services\AttentionNotificationService;
use App\Services\StudentService;
use App\Services\TeacherDashboardService;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentService $students,
        private readonly TeacherDashboardService $dashboard,
        private readonly AttentionNotificationService $attentionNotifications,
    ) {}

    /**
     * The student overview (US-602), which also carries the teacher dashboard
     * (US-609): /students is where AuthController::homeFor lands teachers after
     * login, and a separate summary page would only duplicate the roster it
     * must show, so the system-wide summary composes the existing services and
     * renders above the same filtered roster. No student identifier is
     * accepted, so a request that tries to pivot the list onto a specific user
     * fails loudly (403) rather than silently being ignored.
     */
    public function __invoke(StudentOverviewRequest $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'Student overview takes no student identifier.');
        }

        $filters = $request->safe(['q', 'course', 'status']);

        $paginatorQuery = array_filter($filters, fn ($value): bool => $value !== null);

        $rows = $this->students->index(
            $filters['q'] ?? null,
            isset($filters['course']) ? (int) $filters['course'] : null,
            $filters['status'] ?? null,
            $paginatorQuery,
        );

        $overview = $this->dashboard->overview();

        /** @var User $user */
        $user = auth()->user();

        // US-806: the teacher's notification center is synced lazily from the
        // needs-attention list whenever they open the dashboard. The sync is
        // the one deliberate write exception inside this teacher-area handler
        // (see .ai/rules/controllers.md); it runs only after the IDOR guard
        // and after the page data is composed.
        $this->attentionNotifications->syncFor($user);

        return view('students', [
            'role' => $user->role,
            'students' => $rows,
            'filterCourses' => $this->students->courseOptions(),
            'filters' => $filters,
            'dashboardMetrics' => $overview['metrics'],
            'dashboardActivity' => $overview['recent_activity'],
            'dashboardSummary' => $overview['assessment_summary'],
        ]);
    }
}
