<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class XpLedgerController extends Controller
{
    public function __construct(
        private readonly XpService $xp,
    ) {}

    /**
     * The XP Ledger history page (US-506). Strictly scoped to the
     * authenticated student: no user-suppliable identifier is accepted, so a
     * request that tries to name another student's ledger fails loudly (403)
     * rather than silently showing the caller's own rows (§17).
     */
    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'The XP ledger is scoped to your own account.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('xp-ledger', [
            'role' => $user->role,
            'balance' => $this->xp->balance($user),
            'transactions' => $this->xp->transactionHistory($user),
        ]);
    }
}
