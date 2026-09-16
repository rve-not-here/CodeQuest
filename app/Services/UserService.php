<?php

namespace App\Services;

use App\Exceptions\UserProtectionException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

/**
 * Admin user management (US-703, §9.0–§12.0): a server-side searched, filtered,
 * and paginated directory of every account, plus whitelisted create/update
 * paths. The controller and requests never hand raw client input to the model:
 * the server decides which fields are writable, validates them all, and the
 * password is always a fresh hash of the plaintext the client submits.
 *
 * - search and role filter run in SQL (no full-table-to-JS, §9.0);
 * - "last activity" reuses TimelineService::events — one beat vocabulary, no
 *   new activity mechanism (§9.0);
 * - create keeps status on its 'active' column default and allows only
 *   CREATABLE_ROLES; update additionally carries optional role/status changes
 *   that are validated (US-704) and guarded against self-demotion /
 *   self-deactivation / dropping the active-admin fleet below two (§13).
 *
 * Username search uses `%term%` LIKE, same shape as StudentService. Rows are
 * computed for every matching user then paginated in memory; acceptable at
 * fleet scale and deliberately consistent with the roster's approach.
 */
class UserService
{
    public const PER_PAGE = 10;

    /**
     * Roles a role change may assign on an EXISTING account (US-704,
     * §12.0/§42.0): the full set, operator included. Creation stays narrower
     * (CREATABLE_ROLES) — operator is a reserved system role no admin UI
     * creates, but an existing account's role change is validated against
     * this exact server-determined set, never an arbitrary string.
     */
    public const ROLES = ['student', 'teacher', 'admin', 'operator'];

    /**
     * Roles an admin may assign when creating an account. 'operator' is
     * excluded: it is a reserved system role with no dedicated admin UI.
     */
    public const CREATABLE_ROLES = ['student', 'teacher', 'admin'];

    public function __construct(
        private readonly TimelineService $timeline,
        private readonly AdminAuditService $audit,
    ) {}

    /**
     * @param  array<string, string>  $paginatorQuery
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function index(?string $search, ?string $role, array $paginatorQuery): LengthAwarePaginator
    {
        $users = User::query()
            ->when($search !== null, fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"),
            ))
            ->when($role !== null, fn ($query) => $query->where('role', $role))
            ->orderBy('username')
            ->get();

        $rows = $users->map(fn (User $user): array => $this->rowFor($user));

        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $rows->forPage($page, self::PER_PAGE),
            $rows->count(),
            self::PER_PAGE,
            $page,
            [
                'path' => route('admin.users'),
                'query' => $paginatorQuery,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function rowFor(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'role' => $user->role,
            'status' => $user->status,
            'createdAt' => $user->created_at,
            'lastActivity' => $this->lastActivity($user),
        ];
    }

    /**
     * Newest learning beat in the SAME four-source vocabulary as the student
     * timeline (TimelineService::events), so the directory column and the
     * timeline can never disagree about what a user did last. Returns null for
     * accounts with no learning activity (e.g. fresh teachers/admins).
     *
     * @return array{message: string, at: string}|null
     */
    private function lastActivity(User $user): ?array
    {
        $beat = $this->timeline->events($user, 1)->first();

        if ($beat === null) {
            return null;
        }

        return [
            'message' => $beat['label'],
            'at' => $beat['at']->diffForHumans(),
        ];
    }

    /**
     * Newest learning beats for the user detail page.
     *
     * @return Collection<int, array{at: Carbon, label: string, type: string, pts: int|null, seq: int}>
     */
    public function recentActivity(User $user, int $limit = 5): Collection
    {
        return $this->timeline->events($user, $limit);
    }

    /**
     * Create an account. Whitelisted, server-decided fields only — status stays
     * on its 'active' column default, operator is unreachable (CREATABLE_ROLES),
     * and the password is hashed by the model's 'hashed' cast from the plaintext
     * the request supplied. Records a 'user.create' audit row against the new
     * account (US-709: the creation itself is a mutation that must be on the
     * trail, not just later role/status edits).
     *
     * @param  array{username: string, name: string, role: string, password: string}  $attributes
     */
    public function create(User $actor, array $attributes): User
    {
        $user = User::query()->create([
            'username' => $attributes['username'],
            'name' => $attributes['name'],
            'role' => $attributes['role'],
            'password' => $attributes['password'],
        ]);

        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_USER_CREATE,
            "User created: username → '{$user->username}', name → '{$user->name}', role → '{$user->role}'",
            targetType: 'user',
            targetId: $user->id,
        );

        return $user;
    }

    /**
     * Update an existing account's editable fields plus optional guarded
     * role/status changes (US-704, §13). Username and name are always
     * writable; password is re-hashed only when a non-empty value is
     * supplied. Role must be one of ROLES and status one of active|inactive —
     * both optional, applied only when present and actually different.
     *
     * Guards run BEFORE any mutation: an admin may not change their own role
     * away from admin, may not deactivate themselves, and no change may leave
     * the fleet with fewer than two active admins. Every refusal writes a
     * failed audit row against the target account, then throws
     * UserProtectionException.
     *
     * Base-field changes (username/name/password) each record a 'user.update'
     * row — mirroring CourseService's 'field → value' summary vocabulary — and
     * an unchanged base field writes nothing (a no-op round-trip is silent).
     * The password is never echoed into the summary; it is reported as a
     * (changed) token.
     *
     * @param  array{username: string, name: string, password?: string|null, role?: string|null, status?: string|null}  $attributes
     */
    public function update(User $actor, User $target, array $attributes): User
    {
        $baseChanges = [];
        $fieldSummary = [];

        if ($attributes['username'] !== $target->username) {
            $baseChanges['username'] = $attributes['username'];
            $fieldSummary[] = "username → '{$attributes['username']}'";
        }

        if ($attributes['name'] !== $target->name) {
            $baseChanges['name'] = $attributes['name'];
            $fieldSummary[] = "name → '{$attributes['name']}'";
        }

        if (! empty($attributes['password']) && ! Hash::check($attributes['password'], $target->password)) {
            $baseChanges['password'] = $attributes['password'];
            $fieldSummary[] = 'password → (changed)';
        }

        $changes = array_merge([
            'username' => $attributes['username'],
            'name' => $attributes['name'],
        ], $baseChanges);

        $role = $attributes['role'] ?? null;
        $status = $attributes['status'] ?? null;

        if ($role !== null && ! in_array($role, self::ROLES, true)) {
            $this->refuse($actor, $target, "Unknown role '{$role}'.", AdminAuditService::ACTION_ROLE_CHANGE);
        }

        if ($status !== null && ! in_array($status, ['active', 'inactive'], true)) {
            $this->refuse($actor, $target, "Unknown status '{$status}'.", AdminAuditService::ACTION_STATUS_CHANGE);
        }

        // Refusals are decided against the unchanged record, before anything
        // is written, so a blocked request leaves the target untouched.
        if ($this->roleChangeRemovesActiveAdmin($target, $role)) {
            $this->assertRoleChangeAllowed($actor, $target);
        }

        if ($this->statusChangeRemovesActiveAdmin($target, $status)) {
            $this->assertStatusChangeAllowed($actor, $target);
        }

        $target->update($changes);
        $target->refresh();

        if ($fieldSummary !== []) {
            $this->audit->record(
                $actor,
                AdminAuditService::ACTION_USER_UPDATE,
                'User updated: '.implode(', ', $fieldSummary),
                targetType: 'user',
                targetId: $target->id,
            );
        }

        if ($role !== null && $role !== $target->role) {
            $from = $target->role;
            $target->role = $role;
            $target->save();
            $this->audit->record(
                $actor,
                AdminAuditService::ACTION_ROLE_CHANGE,
                "Role changed: {$from} → {$role}",
                targetType: 'user',
                targetId: $target->id,
            );
        }

        if ($status !== null && $status !== $target->status) {
            $from = $target->status;
            $target->status = $status;
            $target->save();
            $this->audit->record(
                $actor,
                AdminAuditService::ACTION_STATUS_CHANGE,
                "Status changed: {$from} → {$status}",
                targetType: 'user',
                targetId: $target->id,
            );
        }

        return $target;
    }

    /**
     * Record a failed user-mutation attempt, then throw. Consistent with
     * CourseService's refusal flow: the ledger carries the row before the
     * exception propagates.
     *
     * @return never
     *
     * @throws InvalidArgumentException
     */
    private function refuse(User $actor, User $target, string $reason, string $action): void
    {
        $this->audit->record(
            $actor,
            $action,
            'Refused: '.$reason,
            'failed',
            'user',
            $target->id,
        );

        throw new InvalidArgumentException($reason);
    }

    /**
     * True when the proposed role change would remove the target from the
     * active-admin set (a promotion, a no-op, or a change to a deactivated or
     * non-admin account never can).
     */
    private function roleChangeRemovesActiveAdmin(User $target, ?string $role): bool
    {
        return $role !== null
            && $target->role === 'admin'
            && $target->status === 'active'
            && $role !== 'admin';
    }

    /**
     * True when the proposed status change would deactivate an active admin.
     */
    private function statusChangeRemovesActiveAdmin(User $target, ?string $status): bool
    {
        return $status !== null
            && $target->role === 'admin'
            && $target->status === 'active'
            && $status !== 'active';
    }

    private function assertRoleChangeAllowed(User $actor, User $target): void
    {
        $exception = match (true) {
            $actor->is($target) => UserProtectionException::selfRoleChange(),
            $this->activeAdminCount() <= 2 => UserProtectionException::lastActiveAdmin(),
            default => null,
        };

        if ($exception === null) {
            return;
        }

        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_ROLE_CHANGE,
            'Refused: '.$exception->getMessage(),
            'failed',
            'user',
            $target->id,
        );

        throw $exception;
    }

    private function assertStatusChangeAllowed(User $actor, User $target): void
    {
        $exception = match (true) {
            $actor->is($target) => UserProtectionException::selfDeactivation(),
            $this->activeAdminCount() <= 2 => UserProtectionException::lastActiveAdmin(),
            default => null,
        };

        if ($exception === null) {
            return;
        }

        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_STATUS_CHANGE,
            'Refused: '.$exception->getMessage(),
            'failed',
            'user',
            $target->id,
        );

        throw $exception;
    }

    private function activeAdminCount(): int
    {
        return User::query()
            ->where('role', 'admin')
            ->where('status', 'active')
            ->count();
    }
}
