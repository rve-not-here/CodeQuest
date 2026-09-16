<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AttentionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttentionController extends Controller
{
    public function __construct(
        private readonly AttentionService $attention,
    ) {}

    /**
     * Students needing attention (US-608). The page spans the whole roster
     * and takes no input; a request that tries to pivot the view onto a
     * specific user fails loudly (403), the same posture as the student
     * overview and course analytics. Signals are computed server-side per
     * §19.0-§21.0 and are never a grade.
     */
    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'Students needing attention takes no student identifier.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('needs-attention', [
            'role' => $user->role,
            'students' => $this->attention->list(),
        ]);
    }
}
