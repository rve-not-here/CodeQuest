<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ClassroomAccessService;
use App\Services\CourseAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseAnalyticsController extends Controller
{
    public function __construct(
        private readonly CourseAnalyticsService $analytics,
        private readonly ClassroomAccessService $access,
    ) {}

    /**
     * Per-course aggregate summaries (US-607). The page spans the requesting
     * user's monitorable scope — the whole fleet for an admin, the students
     * and courses of the teacher's ACTIVE classrooms. It takes no student
     * identifier: a request that tries to pivot the view onto a specific user
     * fails loudly (403), the same posture as the student overview.
     */
    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'Course analytics takes no student identifier.');
        }

        /** @var User $user */
        $user = auth()->user();

        $scope = $this->access->scopesFor($user);

        return view('course-analytics', [
            'role' => $user->role,
            'courses' => $this->analytics->overview($scope['studentIds'], $scope['courseIds'], $scope['byStudent']),
        ]);
    }
}
