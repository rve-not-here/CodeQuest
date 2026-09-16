<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CompetencyService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompetencyController extends Controller
{
    public function __construct(
        private readonly CompetencyService $competencies,
    ) {}

    /**
     * The Competency Dashboard (US-507). Strictly scoped to the authenticated
     * student: no user-suppliable identifier is accepted, so a request that
     * tries to name another student's competencies fails loudly (403) rather
     * than silently showing the caller's own rows (§17).
     */
    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'Competency is scoped to your own account.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('competency', [
            'role' => $user->role,
            'competencies' => $this->competencies->overview($user),
        ]);
    }
}
