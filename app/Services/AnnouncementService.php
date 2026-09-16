<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The only write path for admin-authored announcements (US-807, §30.0–§33.0).
 * Announcements live on a draft → published → archived lifecycle; audience
 * (all|students|teachers|admins) is server-determined and decides who receives
 * a SYSTEM_ANNOUNCEMENT notification at publish. The operator role is
 * deliberately excluded from every audience.
 *
 * publish() is FIRST-PUBLISH-ONLY (§31.0): published_at is set once on the
 * first publish, fanning out one notification per matching ACTIVE user
 * (dedupe_key announcement:{id}, unique per user). A later edit of a
 * published announcement never re-notifies, archiving is silent, and
 * re-announcing means creating a new announcement. The recursion guard is the
 * state gate: only 'draft' can be published, only 'published' can be
 * archived, and an archived announcement is read-only.
 *
 * Every refusal records a 'failed' audit row before the exception is thrown,
 * the same discipline as CourseService/UserService; every applied change
 * records a 'success' row, and a no-op round-trip records nothing.
 */
class AnnouncementService
{
    /**
     * The server-determined audience set, mirrored from the the404_announcements
     * enum column. The request validates first; the service re-validates as
     * defense-in-depth. Operator is never a deliverable audience.
     */
    public const AUDIENCES = ['all', 'students', 'teachers', 'admins'];

    /**
     * The publication lifecycle states (draft → published → archived).
     */
    public const STATUSES = ['draft', 'published', 'archived'];

    /**
     * The roles each audience reaches. 'all' spans every non-operator
     * learning/teaching role; the operator is excluded from delivery by
     * design (§9) and is not the target of any audience.
     *
     * @var array<string, array<int, string>>
     */
    private const AUDIENCE_ROLES = [
        'all' => ['student', 'teacher', 'admin'],
        'students' => ['student'],
        'teachers' => ['teacher'],
        'admins' => ['admin'],
    ];

    public function __construct(
        private readonly NotificationService $notifications,
        private readonly AdminAuditService $audit,
    ) {}

    /**
     * Every announcement, newest first, with the author eager-loaded for the
     * admin listing.
     *
     * @return Collection<int, Announcement>
     */
    public function index(): Collection
    {
        return Announcement::query()
            ->with('creator')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Create an announcement as a draft. status is set by the server, never
     * by the client — every new announcement lands in 'draft' and only the
     * publish action moves it forward. Records an 'announcement.create'
     * success row against the new announcement.
     *
     * @param  array{title: string, message: string, audience: string}  $attributes
     */
    public function create(User $actor, array $attributes): Announcement
    {
        $announcement = Announcement::query()->create([
            'created_by' => $actor->id,
            'title' => $attributes['title'],
            'message' => $attributes['message'],
            'audience' => $attributes['audience'],
            'status' => 'draft',
        ]);

        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_ANNOUNCEMENT_CREATE,
            "Announcement created: '{$announcement->title}', audience → {$announcement->audience}",
            targetType: 'announcement',
            targetId: $announcement->id,
        );

        return $announcement;
    }

    /**
     * Edit a draft or published announcement's title/message/audience. An
     * archived announcement is read-only. Editing a published announcement
     * NEVER re-notifies (§31): the delivered rows are first-publish-only and
     * this write touches only the announcement row, never the404_notifications
     * (no retroactive rewrite of delivered messages).
     *
     * Allow-list checks and the archived guard run BEFORE any write. A no-op
     * round-trip records no audit row; refusals record a 'failed' row first.
     *
     * @param  array{title: string, message: string, audience: string}  $attributes
     */
    public function update(User $actor, Announcement $announcement, array $attributes): Announcement
    {
        if ($announcement->status === 'archived') {
            $this->refuse($actor, $announcement, 'An archived announcement is read-only.', AdminAuditService::ACTION_ANNOUNCEMENT_UPDATE);
        }

        $title = (string) $attributes['title'];
        $message = (string) $attributes['message'];
        $audience = (string) $attributes['audience'];

        if (trim($title) === '') {
            $this->refuse($actor, $announcement, 'Announcement title must be a non-empty string.', AdminAuditService::ACTION_ANNOUNCEMENT_UPDATE);
        }

        if (trim($message) === '') {
            $this->refuse($actor, $announcement, 'Announcement message must be a non-empty string.', AdminAuditService::ACTION_ANNOUNCEMENT_UPDATE);
        }

        if (! in_array($audience, self::AUDIENCES, true)) {
            $this->refuse($actor, $announcement, "Unknown audience '{$audience}'.", AdminAuditService::ACTION_ANNOUNCEMENT_UPDATE);
        }

        $changes = [];
        $summary = [];

        if ($title !== $announcement->title) {
            $changes['title'] = $title;
            $summary[] = "title → '{$title}'";
        }

        if ($message !== $announcement->message) {
            $changes['message'] = $message;
            $summary[] = 'message → (updated)';
        }

        if ($audience !== $announcement->audience) {
            $changes['audience'] = $audience;
            $summary[] = "audience → '{$audience}'";
        }

        if ($changes === []) {
            return $announcement;
        }

        $announcement->update($changes);

        $this->audit->record(
            $actor,
            AdminAuditService::ACTION_ANNOUNCEMENT_UPDATE,
            'Announcement updated: '.implode(', ', $summary),
            targetType: 'announcement',
            targetId: $announcement->id,
        );

        return $announcement;
    }

    /**
     * Publish an announcement: first-publish-only (§31). Guards run BEFORE
     * any write (and before the transaction) so a refused row always persists
     * on the trail; the success audit row plus the notification fan-out run
     * inside ONE transaction so a rollback removes the fan-out too (§19).
     *
     * @throws InvalidArgumentException
     */
    public function publish(User $actor, Announcement $announcement): Announcement
    {
        if ($announcement->status === 'published') {
            $this->refuse($actor, $announcement, 'This announcement has already been published.', AdminAuditService::ACTION_ANNOUNCEMENT_PUBLISH);
        }

        if ($announcement->status === 'archived') {
            $this->refuse($actor, $announcement, 'An archived announcement cannot be published.', AdminAuditService::ACTION_ANNOUNCEMENT_PUBLISH);
        }

        if (trim((string) $announcement->title) === '' || trim((string) $announcement->message) === '') {
            $this->refuse($actor, $announcement, 'A published announcement needs both a title and a message.', AdminAuditService::ACTION_ANNOUNCEMENT_PUBLISH);
        }

        if (! in_array($announcement->audience, self::AUDIENCES, true)) {
            $this->refuse($actor, $announcement, "Unknown audience '{$announcement->audience}'.", AdminAuditService::ACTION_ANNOUNCEMENT_PUBLISH);
        }

        DB::transaction(function () use ($actor, $announcement): void {
            $announcement->status = 'published';
            $announcement->published_at = now();
            $announcement->save();

            $this->audit->record(
                $actor,
                AdminAuditService::ACTION_ANNOUNCEMENT_PUBLISH,
                "Announcement published: '{$announcement->title}' → {$announcement->audience}",
                targetType: 'announcement',
                targetId: $announcement->id,
            );

            $this->announce($announcement);
        });

        return $announcement;
    }

    /**
     * Archive a published announcement (terminal state, §31/§32). Only a
     * published announcement can be archived; archiving is silent — it does
     * NOT notify and never touches delivered notification rows. A success
     * 'announcement.archive' row records the transition.
     *
     * @throws InvalidArgumentException
     */
    public function archive(User $actor, Announcement $announcement): Announcement
    {
        if ($announcement->status !== 'published') {
            $this->refuse($actor, $announcement, 'Only a published announcement can be archived.', AdminAuditService::ACTION_ANNOUNCEMENT_ARCHIVE);
        }

        DB::transaction(function () use ($actor, $announcement): void {
            $announcement->status = 'archived';
            $announcement->save();

            $this->audit->record(
                $actor,
                AdminAuditService::ACTION_ANNOUNCEMENT_ARCHIVE,
                "Announcement archived: '{$announcement->title}'",
                targetType: 'announcement',
                targetId: $announcement->id,
            );
        });

        return $announcement;
    }

    /**
     * Fan out one SYSTEM_ANNOUNCEMENT notification per matching ACTIVE user.
     * The audience map is the single server-determined reach decision; the
     * dedupe key announcement:{id} makes the delivery idempotent per user and
     * the (user_id, dedupe_key) unique index is the backstop. Title and
     * message pass through verbatim — no reformatting vocabulary.
     *
     * @throws InvalidArgumentException
     */
    private function announce(Announcement $announcement): void
    {
        $roles = self::AUDIENCE_ROLES[$announcement->audience] ?? [];

        $recipients = User::query()
            ->where('status', 'active')
            ->whereIn('role', $roles)
            ->get();

        foreach ($recipients as $recipient) {
            $this->notifications->create(
                $recipient,
                NotificationService::TYPE_SYSTEM_ANNOUNCEMENT,
                $announcement->title,
                $announcement->message,
                "announcement:{$announcement->id}",
                NotificationService::payload('notifications'),
            );
        }
    }

    /**
     * Record a failed announcement-mutation attempt, then throw. Consistent
     * with CourseService/UserService: the ledger carries the row before the
     * exception propagates.
     *
     * @return never
     *
     * @throws InvalidArgumentException
     */
    private function refuse(User $actor, Announcement $announcement, string $reason, string $action): void
    {
        $this->audit->record(
            $actor,
            $action,
            'Refused: '.$reason,
            'failed',
            'announcement',
            $announcement->id,
        );

        throw new InvalidArgumentException($reason);
    }
}
