<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Mission;
use App\Models\MissionDraft;
use App\Models\Notification;
use App\Models\Progress;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Student reminders (US-809, §34.0-§36.0).
 *
 * The student-facing counterpart to AttentionNotificationService: two
 * frequency-governed recurring notifications, both change-driven, both written
 * lazily when the authenticated student opens GET /dashboard.
 *
 *  - DRAFT_REMINDER: a saved mission draft left untouched for
 *    DRAFT_FIRST_REMINDER_DAYS (3) and then DRAFT_FINAL_REMINDER_DAYS (7).
 *    Monotone per draft: the highest stage already delivered is stored next to
 *    the row (data.stage) and a new row is written only when a higher stage
 *    comes due, so a draft left open for months yields at most two reminders.
 *  - LEARNING_REMINDER: the student's current course, in the spec's priority
 *    order — (1) a stalled course (some but not all missions done, last
 *    completion at least AttentionService::STALL_DAYS ago) or (2) every mission
 *    done with the unlocked Boss Challenge never attempted. The evaluated state
 *    is stored as a snapshot (data.course + data.kind) and a new row is written
 *    only when that snapshot changes, so daily dashboard visits while the
 *    situation stands produce exactly one reminder, not one per visit.
 *
 * Hard exclusions the spec names: a sealed (locked/draft) course, a course with
 * zero missions, completed work (a draft whose mission already has a Progress
 * row, a course already completed), and an already-attempted challenge. Inactive
 * accounts never reach here at all — EnsureUserIsActive locks them out before
 * the controller runs. Non-students are a no-op inside syncFor().
 *
 * Cost note (the same acknowledged price as US-806): /dashboard already
 * resolves the current course, and turning it into a reminder re-resolves it
 * through DashboardService::currentCourse(), so that traversal runs twice per
 * student dashboard request. Reusing the single source of current-course truth
 * is deliberate — a parallel course derivation here would be a formula by
 * decree.
 */
class StudentReminderService
{
    /** First draft reminder: three untouched calendar days. */
    public const DRAFT_FIRST_REMINDER_DAYS = 3;

    /** Final draft reminder: a week untouched. */
    public const DRAFT_FINAL_REMINDER_DAYS = 7;

    /** The two draft reminder stages, stored as data.stage. */
    public const DRAFT_STAGE_FIRST = 3;

    public const DRAFT_STAGE_FINAL = 7;

    /** The two learning reminder shapes, stored as data.kind. */
    public const KIND_STALLED = 'stalled';

    public const KIND_ASSESSMENT = 'assessment';

    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly AssessmentService $assessments,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Evaluate and emit the student's reminders; returns the number of rows
     * created. Non-students are a no-op — this vocabulary is the student's own.
     */
    public function syncFor(User $user): int
    {
        if ($user->role !== 'student') {
            return 0;
        }

        return DB::transaction(function () use ($user): int {
            return $this->syncDraftReminders($user) + $this->syncLearningReminder($user);
        });
    }

    /**
     * Emit one draft reminder per draft that has crossed a stage it has not
     * yet been reminded for. Drafts on a sealed course or a mission already
     * completed are skipped.
     */
    private function syncDraftReminders(User $user): int
    {
        $drafts = MissionDraft::query()
            ->where('user_id', $user->id)
            ->whereHas('mission', function ($query): void {
                $query->whereHas('course', function ($query): void {
                    $query->where('status', 'active');
                });
            })
            ->with('mission')
            ->get();

        if ($drafts->isEmpty()) {
            return 0;
        }

        $latestStageByMission = $this->latestDraftStageByMission($user);
        $created = 0;

        foreach ($drafts as $draft) {
            $mission = $draft->mission;

            if (! $mission instanceof Mission) {
                continue;
            }

            if ($this->isMissionCompleted($user, $mission)) {
                continue;
            }

            $stage = $this->draftStageFor($draft->updated_at);

            if ($stage === null || $stage <= ($latestStageByMission[$mission->id] ?? 0)) {
                continue;
            }

            $this->notifications->create(
                $user,
                NotificationService::TYPE_DRAFT_REMINDER,
                'CHALLENGE DRAFT AWAITING',
                $this->draftMessage($mission, $stage, $draft->updated_at),
                null,
                NotificationService::payload(
                    'mission.show',
                    ['mission' => $mission->id],
                    ['mission' => $mission->id, 'stage' => $stage],
                ),
            );

            $created++;
        }

        return $created;
    }

    /**
     * Emit the current course's learning reminder when its snapshot (course +
     * kind) differs from the last one delivered.
     */
    private function syncLearningReminder(User $user): int
    {
        $course = $this->dashboard->currentCourse($user);

        if ($course === null) {
            return 0;
        }

        $progress = $this->dashboard->courseProgress($user, $course);
        $completed = $progress['completed'];
        $total = $progress['total'];

        $kind = null;
        $daysSinceLastCompletion = 0;

        if ($completed > 0 && $completed < $total) {
            $lastCompletion = $this->lastCompletionAt($user, $course);

            if ($lastCompletion !== null && $this->daysSince($lastCompletion) >= AttentionService::STALL_DAYS) {
                $kind = self::KIND_STALLED;
                $daysSinceLastCompletion = $this->daysSince($lastCompletion);
            }
        } elseif ($completed === $total
            && $this->assessments->isUnlocked($user, $course)
            && $this->assessments->latestAttemptFor($user, $course) === null
        ) {
            $kind = self::KIND_ASSESSMENT;
        }

        if ($kind === null) {
            return 0;
        }

        if ($this->latestLearningSnapshot($user) === ['course' => $course->id, 'kind' => $kind]) {
            return 0;
        }

        $this->notifications->create(
            $user,
            NotificationService::TYPE_LEARNING_REMINDER,
            $kind === self::KIND_STALLED ? 'COURSE STALLED' : 'CHALLENGE READY',
            $this->learningMessage($course, $kind, $daysSinceLastCompletion, $completed, $total),
            null,
            NotificationService::payload('learning-path', [], ['course' => $course->id, 'kind' => $kind]),
        );

        return 1;
    }

    /**
     * The stage a draft's age currently earns, or null before the first
     * threshold. Monotone: the final stage is a superset of the first.
     */
    private function draftStageFor(CarbonInterface $updatedAt): ?int
    {
        $days = $this->daysSince($updatedAt);

        if ($days >= self::DRAFT_FINAL_REMINDER_DAYS) {
            return self::DRAFT_STAGE_FINAL;
        }

        if ($days >= self::DRAFT_FIRST_REMINDER_DAYS) {
            return self::DRAFT_STAGE_FIRST;
        }

        return null;
    }

    private function draftMessage(Mission $mission, int $stage, CarbonInterface $updatedAt): string
    {
        $days = $this->daysSince($updatedAt);

        if ($stage === self::DRAFT_STAGE_FINAL) {
            return sprintf('Your draft for "%s" has been untouched for %d days. Return before it goes cold.', $mission->title, $days);
        }

        return sprintf('Your saved draft for "%s" is waiting (%d days). Pick it back up.', $mission->title, $days);
    }

    private function learningMessage(Course $course, string $kind, int $days, int $completed, int $total): string
    {
        if ($kind === self::KIND_STALLED) {
            return sprintf(
                'No new challenge on "%s" in %d days (%d/%d challenges). Pick it back up.',
                $course->name,
                $days,
                $completed,
                $total,
            );
        }

        return sprintf('You have finished every challenge in "%s". The Boss Challenge is ready; take it on.', $course->name);
    }

    private function isMissionCompleted(User $user, Mission $mission): bool
    {
        return Progress::query()
            ->where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->exists();
    }

    /**
     * The newest completion time across the course's missions, the same
     * MAX(completed_at) derivation AttentionService's stall signal uses.
     */
    private function lastCompletionAt(User $user, Course $course): ?CarbonInterface
    {
        $value = Progress::query()
            ->where('user_id', $user->id)
            ->whereIn('mission_id', $course->missions()->select('id'))
            ->max('completed_at');

        return $value !== null ? Carbon::parse((string) $value) : null;
    }

    /**
     * The highest draft stage already delivered per mission, keyed by mission
     * id. One bounded query for the requesting student — no per-draft fan-out.
     *
     * @return array<int, int>
     */
    private function latestDraftStageByMission(User $user): array
    {
        $rows = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', NotificationService::TYPE_DRAFT_REMINDER)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $latest = [];

        foreach ($rows as $row) {
            $data = $row->data;
            $missionId = is_array($data) ? ($data['mission'] ?? null) : null;
            $stage = is_array($data) ? ($data['stage'] ?? null) : null;

            if (! (is_int($missionId) || (is_string($missionId) && is_numeric($missionId)))) {
                continue;
            }

            $key = (int) $missionId;

            if (! array_key_exists($key, $latest)) {
                $latest[$key] = is_numeric($stage) ? (int) $stage : 0;
            }
        }

        return $latest;
    }

    /**
     * The last-delivered learning snapshot, or null when none exists. Only
     * course + kind participate, so the passage of time alone never re-emits.
     *
     * @return array{course: int, kind: string}|null
     */
    private function latestLearningSnapshot(User $user): ?array
    {
        $row = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', NotificationService::TYPE_LEARNING_REMINDER)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($row === null) {
            return null;
        }

        $data = $row->data;
        $course = is_array($data) ? ($data['course'] ?? null) : null;
        $kind = is_array($data) ? ($data['kind'] ?? null) : null;

        if (! (is_int($course) || (is_string($course) && is_numeric($course))) || ! is_string($kind)) {
            return null;
        }

        return ['course' => (int) $course, 'kind' => $kind];
    }

    /**
     * Whole calendar days between a timestamp's day and today's day, snapped
     * to start-of-day on both sides — the exact convention AttentionService
     * uses, so the stall threshold cannot drift between the two services.
     */
    private function daysSince(CarbonInterface $at): int
    {
        return abs((int) now()->startOfDay()->diffInDays($at->copy()->startOfDay()));
    }
}
