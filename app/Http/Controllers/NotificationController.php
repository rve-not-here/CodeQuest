<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * The notification center (US-801 gateway + US-802 list, §12/§13).
     * Strictly scoped to the authenticated user: an IDOR probe that tries to
     * name another user fails loudly (403) before any resolution — there is
     * no user-suppliable identifier, and no parameter can retrieve another
     * user's notification. The feed is the caller's own paginated history and
     * the unread badge is a SQL count over the (user_id, read_at) index.
     */
    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'The notification center is scoped to your own account.');
        }

        /** @var User $user */
        $user = auth()->user();

        $notifications = $this->notifications->forUser($user);

        // Safe inbound links for this page's rows (US-804/805): computed
        // once per page from each owned row's server-authored data, via the
        // same TYPE_ROUTES allowlist that guards rendering. A null means
        // "no link" — the view renders the plain title.
        $links = collect($notifications->items())
            ->mapWithKeys(fn (Notification $notification): array => [
                $notification->id => $this->notifications->linkFor($notification),
            ])
            ->all();

        return view('notifications', [
            'role' => $user->role,
            'notifications' => $notifications,
            'unreadCount' => $this->notifications->unreadCount($user),
            'links' => $links,
        ]);
    }

    /**
     * Mark one notification read (US-803, §14/§42). The {notification} path
     * id is the intended identifier, so there is no user-scoping query
     * parameter to purge; ownership is enforced server-side by reusing
     * findForUser inside the service. A foreign or nonexistent id is the same
     * no-op as an already-read row — the response shape never differs, so a
     * read-state IDOR probe still cannot distinguish "not yours" from
     * "doesn't exist".
     */
    public function markRead(Request $request, int $notification): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $this->notifications->markAsRead($user, $notification)) {
            return redirect()->route('notifications');
        }

        return redirect()->route('notifications')->with('notification_success', [
            'title' => 'MARKED AS READ',
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark every notification read (US-803, §14/§42). Server-scoped to the
     * authenticated user in SQL; returns the number of rows changed so the
     * flash can report it, and a no-op round-trip shows no success message.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $changed = $this->notifications->markAllAsRead($user);

        if ($changed === 0) {
            return redirect()->route('notifications');
        }

        return redirect()->route('notifications')->with('notification_success', [
            'title' => 'INBOX CLEARED',
            'message' => $changed === 1
                ? '1 notification marked as read.'
                : "{$changed} notifications marked as read.",
        ]);
    }
}
