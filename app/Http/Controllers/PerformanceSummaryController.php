<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PerformanceSummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceSummaryController extends Controller
{
    public function __construct(
        private readonly PerformanceSummaryService $summary,
    ) {}

    /**
     * The student's own Performance Summary. Strictly scoped to the
     * authenticated account: a request that tries to name another student
     * fails loudly (403) rather than silently showing the caller's rows (§17).
     */
    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'The performance summary is scoped to your own account.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('performance', [
            'role' => $user->role,
            'registers' => $this->summary->forStudent($user),
        ]);
    }
}
