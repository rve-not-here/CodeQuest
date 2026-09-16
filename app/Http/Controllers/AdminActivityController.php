<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminActivityFeedRequest;
use App\Models\User;
use App\Services\AdminAuditService;
use Carbon\Carbon;
use Illuminate\View\View;

/**
 * The Administrative Audit Trail (US-709, §27.0), realized on the existing
 * /admin/activity route inside the admin gate. System-wide by design: 'admin'
 * is a role-based, classless gate, so the actor filter can only narrow the
 * view, never widen it — 'actor' is therefore a legit, validated server-side
 * filter here. The untrusted user-scoping spellings are rejected as probes
 * before any data-layer work, matching ActivityController's guard.
 */
class AdminActivityController extends Controller
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function __invoke(AdminActivityFeedRequest $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'owner'])) {
            abort(403, 'The audit trail takes no user identifier.');
        }

        $filters = $request->safe(['actor', 'action', 'result', 'from', 'to']);

        /** @var array<string, string> $paginatorQuery */
        $paginatorQuery = array_filter($filters, static fn (mixed $value): bool => $value !== null);

        $rows = $this->audit->feed(
            isset($filters['actor']) ? (int) $filters['actor'] : null,
            $filters['action'] ?? null,
            $filters['result'] ?? null,
            isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : null,
            isset($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : null,
            $paginatorQuery,
        );

        /** @var User $user */
        $user = auth()->user();

        return view('admin.activity', [
            'role' => $user->role,
            'events' => $rows,
            'filterActors' => $this->audit->actorOptions(),
            'filterActions' => AdminAuditService::ACTIONS,
            'filters' => $filters,
        ]);
    }
}
