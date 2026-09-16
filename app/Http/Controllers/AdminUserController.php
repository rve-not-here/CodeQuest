<?php

namespace App\Http\Controllers;

use App\Exceptions\UserProtectionException;
use App\Http\Requests\AdminUsersIndexRequest;
use App\Http\Requests\AdminUserStoreRequest;
use App\Http\Requests\AdminUserUpdateRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The admin user directory (US-703, §9.0–§12.0). Lists every account with
 * server-side search/filter/pagination, shows per-account detail, and creates
 * or edits accounts. Business logic stays in UserService — this controller only
 * plugs validated request data into it and renders views.
 *
 * The write paths accept only the validated, whitelisted payloads from the
 * form requests. Since US-704, update also carries optional role/status
 * changes whose self-protection and ≥2-active-admin-floor guards live in
 * UserService; a refusal is a UserProtectionException and is flashed back.
 */
class AdminUserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(AdminUsersIndexRequest $request): View
    {
        $filters = $request->safe(['q', 'role']);

        $paginatorQuery = array_filter($filters, fn ($value): bool => $value !== null);

        $rows = $this->users->index(
            $filters['q'] ?? null,
            $filters['role'] ?? null,
            $paginatorQuery,
        );

        /** @var User $user */
        $user = auth()->user();

        return view('admin.users.index', [
            'role' => $user->role,
            'users' => $rows,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return view('admin.users.create', ['role' => $user->role]);
    }

    public function store(AdminUserStoreRequest $request): RedirectResponse
    {
        $payload = $request->safe(['username', 'name', 'role', 'password']);

        /** @var User $actor */
        $actor = auth()->user();

        $user = $this->users->create($actor, $payload);

        return redirect()->route('admin.users.show', $user)
            ->with('status', 'Account created.');
    }

    public function show(User $user): View
    {
        /** @var User $current */
        $current = auth()->user();

        return view('admin.users.show', [
            'role' => $current->role,
            'user' => $user,
            'recentActivity' => $this->users->recentActivity($user),
        ]);
    }

    public function edit(User $user): View
    {
        /** @var User $current */
        $current = auth()->user();

        return view('admin.users.edit', [
            'role' => $current->role,
            'user' => $user,
        ]);
    }

    public function update(AdminUserUpdateRequest $request, User $user): RedirectResponse
    {
        $payload = $request->safe(['username', 'name', 'password', 'role', 'status']);

        /** @var User $actor */
        $actor = auth()->user();

        try {
            $this->users->update($actor, $user, $payload);
        } catch (UserProtectionException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.users.show', $user)
            ->with('status', 'Account updated.');
    }
}
