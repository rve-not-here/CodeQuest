<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\NotificationService;
use App\Services\ResumeService;
use App\Services\StudentReminderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The only types the dashboard teaser may surface (US-810, §29). These are
     * the "act on this or read a broadcast" notifications: an unlocked-but-
     * unattempted assessment, a learning/draft reminder, a teacher that needs
     * attention, and an admin announcement. Instantaneous event feedback
     * (mission_completed, pass/fail, course and achievement events) is excluded
     * so the panel never becomes a ticker that competes with Continue Learning.
     *
     * @var list<string>
     */
    private const PRIORITY_TYPES = [
        NotificationService::TYPE_ASSESSMENT_UNLOCKED,
        NotificationService::TYPE_LEARNING_REMINDER,
        NotificationService::TYPE_DRAFT_REMINDER,
        NotificationService::TYPE_TEACHER_ATTENTION,
        NotificationService::TYPE_SYSTEM_ANNOUNCEMENT,
    ];

    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly ResumeService $resume,
        private readonly StudentReminderService $reminders,
        private readonly NotificationService $notifications,
    ) {}

    public function __invoke(Request $request): View
    {
        if ($request->hasAny(['user_id', 'userId', 'user', 'student', 'owner'])) {
            abort(403, 'The dashboard is scoped to your own account.');
        }

        /** @var User $user */
        $user = auth()->user();

        $course = $this->dashboard->currentCourse($user);
        $progress = $course !== null ? $this->dashboard->courseProgress($user, $course) : null;

        // US-809: the student's reminders are synced lazily from the dashboard
        // — the same deliberate write side-effect on an otherwise read-only
        // GET that US-806 established for the teacher area (see
        // .ai/rules/controllers.md). It runs after the page data is composed,
        // and syncFor() is a no-op for non-students.
        $this->reminders->syncFor($user);

        // US-810: the dashboard teaser is a display-only composition over the
        // existing read paths (NotificationService::forUser / unreadCount), run
        // AFTER the reminder sync so a freshly emitted reminder surfaces on the
        // same visit that produced it. Links go through the same linkFor()
        // allowlist the center uses — no separate, less-guarded rendering path.
        $priorityNotifications = $this->priorityNotifications($user);

        return view('dashboard', [
            'user' => $user,
            'role' => $user->role,
            'course' => $course,
            'courseProgress' => $progress,
            'assessmentReadiness' => $progress !== null
                ? $this->dashboard->assessmentReadiness($progress['completed'], $progress['total'])
                : 0,
            'totalXp' => $this->dashboard->totalXp($user),
            'learnerStats' => $this->dashboard->learnerStats($user),
            'recentActivity' => $this->dashboard->recentActivity($user),
            'resume' => $this->resume->resolve($user),
            'priorityNotifications' => $priorityNotifications,
            'notificationLinks' => collect($priorityNotifications)
                ->mapWithKeys(fn (Notification $notification): array => [
                    $notification->id => $this->notifications->linkFor($notification),
                ])
                ->all(),
            'unreadCount' => $this->notifications->unreadCount($user),
        ]);
    }

    /**
     * The newest priority transmissions from the caller's own feed, capped at
     * two (US-810, §29). forUser() already orders newest-first and is scoped by
     * user_id, so the filter + slice keeps the two newest priority rows and
     * can never reach another user's.
     *
     * @return array<int, Notification>
     */
    private function priorityNotifications(User $user): array
    {
        return collect($this->notifications->forUser($user)->items())
            ->filter(fn (Notification $notification): bool => in_array($notification->type, self::PRIORITY_TYPES, true))
            ->slice(0, 2)
            ->values()
            ->all();
    }
}
