<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Student achievements registry (US-508 display surface). The catalog is the
 * authenticated user's own: no user-suppliable identifier is accepted, so a
 * request that tries to name another user's achievements fails loudly (403)
 * rather than a silent re-scope. Awards themselves remain write-only through
 * AchievementService::award(); this page only renders the ledger.
 */
class AchievementController extends Controller
{
    public function __construct(
        private readonly AchievementService $achievements,
    ) {}

    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'Achievements are scoped to your own account.');
        }

        /** @var User $user */
        $user = auth()->user();

        $catalog = $this->achievements->catalog($user);

        return view('achievements', [
            'role' => $user->role,
            'catalog' => $catalog,
            'earnedCount' => $catalog->filter(fn (array $row): bool => $row['awarded'])->count(),
            'totalCount' => $catalog->count(),
        ]);
    }
}