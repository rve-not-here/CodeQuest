<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SystemStatusService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSystemController extends Controller
{
    public function __construct(private readonly SystemStatusService $status) {}

    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'System status takes no user identifier.');
        }

        /** @var User $user */
        $user = auth()->user();

        return view('admin.system', [
            'role' => $user->role,
            ...$this->status->status(),
        ]);
    }
}
