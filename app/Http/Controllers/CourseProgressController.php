<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CourseProgressService;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseProgressController extends Controller
{
    public function __construct(
        private readonly CourseProgressService $progress,
        private readonly DashboardService $dashboard,
    ) {}

    /**
     * The course progress overview (US-502). Strictly scoped to the
     * authenticated student: no user-suppliable identifier is accepted, so a
     * request that tries to name another student's progress fails loudly (403)
     * rather than silently showing the caller's own rows (§17).
     */
    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'Course progress is scoped to your own account.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('course-progress', [
            'user' => $user,
            'role' => $user->role,
            'overview' => $this->progress->overview($user),
            'totalXp' => $this->dashboard->totalXp($user),
        ]);
    }
}
