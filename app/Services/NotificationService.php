<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

/**
 * The one notification write+read service (US-801..US-806). Every method is
 * keyed by the authenticated user; no method accepts a target-user
 * identifier, so no route or parameter can retrieve another user's
 * notification. Reads are scoped in SQL by user_id, and the
 * single-notification accessor (findForUser) resolves only rows already
 * owned — a foreign or nonexistent id is indistinguishable (null), never a
 * leak. Marking is reuse of the same primitive: markAsRead resolves via
 * findForUser, so a foreign, nonexistent, or already-read row is an
 * identical idempotent no-op.
 *
 * Producers (US-804/805): create() is the only row writer and is called from
 * inside the same DB::transaction as the underlying state change (§19). The
 * data payload is always a route NAME plus params — never a URL — and
 * create() only accepts the exact route a type's TYPE_ROUTES entry allows,
 * so a raw or client-influenced destination cannot be stored (§27/§42).
 * Since US-806 create() also preserves server-authored auxiliary data keys
 * (e.g. the attention signals a teacher_attention row was emitted for) so a
 * producer can store its change-detection state next to the safe link.
 * linkFor() is the one renderer and re-applies the same allowlist before
 * emitting an href, returning null on any mismatch.
 */
class NotificationService
{
    /** The center's page size, matching the other feed services (30). */
    public const FEED_PER_PAGE = 30;

    public const TYPE_MISSION_COMPLETED = 'mission_completed';

    public const TYPE_ASSESSMENT_UNLOCKED = 'assessment_unlocked';

    public const TYPE_ASSESSMENT_PASSED = 'assessment_passed';

    public const TYPE_ASSESSMENT_FAILED = 'assessment_failed';

    public const TYPE_COURSE_COMPLETED = 'course_completed';

    public const TYPE_NEXT_COURSE_UNLOCKED = 'next_course_unlocked';

    public const TYPE_ACHIEVEMENT_EARNED = 'achievement_earned';

    public const TYPE_TEACHER_ATTENTION = 'teacher_attention';

    public const TYPE_SYSTEM_ANNOUNCEMENT = 'system_announcement';

    public const TYPE_DRAFT_REMINDER = 'draft_reminder';

    public const TYPE_LEARNING_REMINDER = 'learning_reminder';

    /**
     * The only link a notification of a given type may target. A producer
     * must register its destination here before it can be produced; the map
     * is the allowlist both create() and linkFor() enforce.
     *
     * @var array<string, string>
     */
    private const TYPE_ROUTES = [
        self::TYPE_MISSION_COMPLETED => 'mission.show',
        self::TYPE_ASSESSMENT_UNLOCKED => 'assessment.show',
        self::TYPE_ASSESSMENT_PASSED => 'assessment.show',
        self::TYPE_ASSESSMENT_FAILED => 'assessment.show',
        self::TYPE_COURSE_COMPLETED => 'learning-path',
        self::TYPE_NEXT_COURSE_UNLOCKED => 'learning-path',
        self::TYPE_ACHIEVEMENT_EARNED => 'achievements',
        self::TYPE_TEACHER_ATTENTION => 'needs-attention',
        // A system announcement IS delivered through the notification center
        // itself (US-807, §9): the row announces itself, so the safe inbound
        // link is the center route rather than a raw destination.
        self::TYPE_SYSTEM_ANNOUNCEMENT => 'notifications',
        // Student reminders (US-809): a stale draft points back at the mission
        // it belongs to; a course-level reminder opens the learning path.
        self::TYPE_DRAFT_REMINDER => 'mission.show',
        self::TYPE_LEARNING_REMINDER => 'learning-path',
    ];

    /**
     * Ownership predicate, the same explicit check AssessmentService exposes
     * as hasAccessToAttempt. A notification belongs to exactly one user.
     */
    public function belongsTo(User $user, Notification $notification): bool
    {
        return (int) $notification->user_id === (int) $user->id;
    }

    /**
     * The paginated notification center feed, newest first (§12/§44). Scoped
     * in SQL by user_id; nothing here accepts a user id parameter. Pagination
     * mirrors the other feed services (LengthAwarePaginator, in-memory page
     * slice of the caller's own rows).
     *
     * @return LengthAwarePaginator<int, Notification>
     */
    public function forUser(User $user): LengthAwarePaginator
    {
        $rows = Notification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $rows->forPage($page, self::FEED_PER_PAGE),
            $rows->count(),
            self::FEED_PER_PAGE,
            $page,
            ['path' => route('notifications')],
        );
    }

    /**
     * Unread count (§13/§44) as a SQL COUNT over the (user_id, read_at)
     * index — never a collection filtered in PHP. The read-state filter is
     * pushed into the query, so the composite index covers the scan.
     */
    public function unreadCount(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark one notification read (§14/§42). Resolves through findForUser, so
     * ownership is enforced by the same primitive as the read path and a
     * foreign or nonexistent id is an identical safe no-op — never a 404/403
     * distinction that would leak existence. Idempotent: an already-read row
     * returns false and never rewrites read_at.
     */
    public function markAsRead(User $user, int $notificationId): bool
    {
        $notification = $this->findForUser($user, $notificationId);

        if ($notification === null || $notification->read_at !== null) {
            return false;
        }

        $notification->read_at = now();
        $notification->save();

        return true;
    }

    /**
     * Mark every unread notification read (§14/§42). Scoped by user_id, so a
     * crafted request can never touch another user's rows; idempotent — a
     * second call updates zero rows. Returns the number of rows changed.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Ownership-gated single-notification lookup. The id resolves only when
     * the row belongs to the caller; a foreign or nonexistent id returns
     * null, so callers cannot distinguish "not yours" from "doesn't exist".
     */
    public function findForUser(User $user, int $notificationId): ?Notification
    {
        return Notification::query()
            ->where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Server-authored destination payload. Only route NAMES are ever passed
     * in — a raw URL cannot be stored because create() only accepts routes
     * on the type's allowlist (§27/§42). Since US-806, server-authored
     * auxiliary keys (e.g. the attention signals a row was emitted for) pass
     * through $auxiliary unchanged; only trusted producers construct
     * payloads, and linkFor() reads the route/params pair alone.
     *
     * @param  array<int|string, mixed>  $params
     * @param  array<array-key, mixed>  $auxiliary
     * @return array{route: string, params: array<int|string, mixed>}&array<array-key, mixed>
     */
    public static function payload(string $route, array $params = [], array $auxiliary = []): array
    {
        return ['route' => $route, 'params' => $params] + $auxiliary;
    }

    /**
     * The sole notification write path. Participates in the caller's existing
     * transaction — never opens its own — so an event rollback silently
     * removes the notification too (§19). The dedupe pre-check runs inside
     * the caller's transaction as well, so a concurrent duplicate commit
     * cannot race past it; the unique (user_id, dedupe_key) DB index is the
     * backstop for any remaining edge case (§17).
     *
     * Returns null when the dedupe key already exists (idempotent no-op);
     * the producer simply discards the null, just like
     * AchievementService::award discards false.
     *
     * @param  array<array-key, mixed>  $data
     */
    public function create(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $dedupeKey,
        array $data,
    ): ?Notification {
        $allowedRoute = self::TYPE_ROUTES[$type] ?? null;

        if ($allowedRoute === null) {
            throw new InvalidArgumentException(
                "Unknown notification type '{$type}'. Register its destination route in TYPE_ROUTES before producing it."
            );
        }

        $route = $data['route'] ?? null;

        if (! is_string($route) || $route !== $allowedRoute) {
            throw new InvalidArgumentException(
                "Notification type '{$type}' may only target route '{$allowedRoute}', got ".var_export($route, true).'.'
            );
        }

        if (! Route::has($route)) {
            throw new InvalidArgumentException(
                "Notification type '{$type}' targets unknown named route '{$route}'."
            );
        }

        $rawParams = $data['params'] ?? null;

        if (! is_array($rawParams)) {
            throw new InvalidArgumentException(
                "Notification type '{$type}' data must carry a params array."
            );
        }

        $params = $this->normalizeParams($rawParams);

        if ($dedupeKey !== null && $this->alreadyProduced($user, $dedupeKey)) {
            return null;
        }

        $auxiliary = array_diff_key($data, ['route' => true, 'params' => true]);

        return Notification::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => ['route' => $route, 'params' => $params] + $auxiliary,
            'dedupe_key' => $dedupeKey,
        ]);
    }

    /**
     * Safe href for a server-produced notification. Re-applies the same
     * TYPE_ROUTES allowlist and Route::has check that create() enforced, so
     * a row tampered with at the database layer still cannot emit a raw URL
     * or link to an unintended internal target — the method simply returns
     * null and the view renders no link.
     */
    public function linkFor(Notification $notification): ?string
    {
        $data = $notification->data;

        if (! is_array($data)) {
            return null;
        }

        $routeName = $data['route'] ?? null;
        $params = $data['params'] ?? null;

        if (! is_string($routeName) || ! is_array($params)) {
            return null;
        }

        $allowed = self::TYPE_ROUTES[$notification->type] ?? null;

        if ($allowed !== $routeName || ! Route::has($routeName)) {
            return null;
        }

        foreach ($params as $value) {
            if (! (is_int($value) || (is_string($value) && is_numeric($value)))) {
                return null;
            }
        }

        $params = array_map(static fn (mixed $value): int => (int) $value, $params);

        return route($routeName, $params);
    }

    /**
     * Whether the user already received an event with this dedupe key.
     * Runs inside the caller's transaction so a concurrent duplicate commit
     * cannot race past this check; the unique (user_id, dedupe_key) DB
     * index is the backstop.
     */
    private function alreadyProduced(User $user, string $dedupeKey): bool
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('dedupe_key', $dedupeKey)
            ->exists();
    }

    /**
     * Ensure every param is a scalar value coercible to int — reject nested
     * arrays, objects, or non-numeric strings. This prevents a malformed
     * payload from passing through to route() unchanged.
     *
     * @param  array<int|string, mixed>  $params
     * @return array<int|string, int>
     */
    private function normalizeParams(array $params): array
    {
        $normalized = [];

        foreach ($params as $key => $value) {
            if (! (is_int($value) || (is_string($value) && is_numeric($value)))) {
                throw new InvalidArgumentException(
                    "Notification param '{$key}' is not a scalar value coercible to int."
                );
            }

            $normalized[$key] = (int) $value;
        }

        return $normalized;
    }
}
