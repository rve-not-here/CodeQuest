<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use App\Services\SectionProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionProgressController extends Controller
{
    public function __construct(
        private readonly SectionProgressService $progress,
        private readonly DashboardService $dashboard,
    ) {}

    /**
     * The section progress overview (US-503). Strictly scoped to the
     * authenticated student: no user-suppliable identifier is accepted, so a
     * request that tries to name another student's progress fails loudly (403)
     * rather than silently showing the caller's own rows (§17).
     */
    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'Section progress is scoped to your own account.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('section-progress', [
            'user' => $user,
            'role' => $user->role,
            'tree' => $this->progress->overview($user),
            'totalXp' => $this->dashboard->totalXp($user),
        ]);
    }
}
