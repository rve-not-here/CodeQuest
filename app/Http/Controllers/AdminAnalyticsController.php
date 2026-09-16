<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAnalyticsController extends Controller
{
    public function __construct(
        private readonly AdminAnalyticsService $analytics,
    ) {}

    /**
     * System Analytics (US-710, §29.0). Purely a read: the page aggregates
     * across the whole fleet and takes no student identifier, so a request
     * that tries to pivot the view onto a specific user fails loudly (403),
     * the same posture as the teacher analytics page. No route here mutates
     * the XP ledger or any balance (§30.0 — administration is view-only).
     */
    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'System analytics takes no user identifier.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('admin.analytics', [
            'role' => $user->role,
            ...$this->analytics->overview(),
        ]);
    }
}
