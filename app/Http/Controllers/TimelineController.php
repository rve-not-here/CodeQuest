<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TimelineService;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimelineController extends Controller
{
    public function __construct(
        private readonly TimelineService $timeline,
        private readonly XpService $xp,
    ) {}

    /**
     * The Unified Learning Timeline (US-505). Strictly scoped to the
     * authenticated student: no user-suppliable identifier is accepted, so a
     * request that tries to name another student's timeline fails loudly
     * (403) rather than silently showing the caller's own rows (§17).
     */
    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'Timeline access is scoped to your own account.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('timeline', [
            'role' => $user->role,
            'events' => $this->timeline->events($user),
            'totalXp' => $this->xp->balance($user),
        ]);
    }
}
