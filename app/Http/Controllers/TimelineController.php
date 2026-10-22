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

        $perPage = 50;
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $perPage;
        $overview = $this->timeline->overview($user, $perPage, null, $offset);
        $lastPage = max(1, (int) ceil($overview['total'] / $perPage));

        if ($page > $lastPage) {
            $page = $lastPage;
            $offset = ($page - 1) * $perPage;
            $overview = $this->timeline->overview($user, $perPage, null, $offset);
        }

        return view('timeline', [
            'role' => $user->role,
            'events' => $overview['events'],
            'eventTotal' => $overview['total'],
            'page' => $page,
            'lastPage' => $lastPage,
            'perPage' => $perPage,
            'totalXp' => $this->xp->balance($user),
        ]);
    }
}
