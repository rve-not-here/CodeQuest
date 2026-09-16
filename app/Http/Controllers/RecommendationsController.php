<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationsController extends Controller
{
    public function __construct(
        private readonly RecommendationService $recommendations,
    ) {}

    /**
     * Personalized Recommendations (US-509). Strictly scoped to the
     * authenticated student: no user-suppliable identifier is accepted, so a
     * request that tries to name another student's recommendations fails
     * loudly (403) rather than silently showing the caller's own rows (§17).
     */
    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'Recommendations are scoped to your own account.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('recommendations', [
            'role' => $user->role,
            'recommendations' => $this->recommendations->recommendations($user),
        ]);
    }
}
