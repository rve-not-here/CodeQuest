<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Notification;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Teacher attention notifications (US-806, §22.0-§25.0).
 *
 * A frequency-governed recurring notification, not a one-shot event: each
 * teacher_attention row targets its recipient's needs-attention inbox, is
 * scoped by ownership exactly like every other notification (a student never
 * receives one — the rows are keyed to the requesting teacher), and is
 * emitted lazily when the teacher opens the dashboard (/students).
 *
 * Emission is change-driven: the ordered attention signal set is stored with
 * each row (data.signals) and a new row is written only when the current set
 * differs from the teacher's latest stored set for that student. This is
 * independently throttled by a 24-hour cooldown, so a student who genuinely
 * changes state inside the window is suppressed now but stays pending (the
 * latest row still carries the previous set) and emits once the cooldown
 * lifts — delayed, never lost.
 *
 * The signal vocabulary is AttentionService::list() verbatim — never a second
 * attention rule set — and the title/message carry only what the
 * needs-attention page already exposes to teachers (username, signal label,
 * evidence reason). Nothing about the student is visible inside the student's
 * own center.
 *
 * Hook cost: /students renders the dashboard through
 * TeacherDashboardService::overview, which counts the same list, and then
 * syncs — AttentionService::list() runs twice per teacher dashboard request.
 * That is the tracked, acknowledged price of reusing the single source of
 * attention truth.
 */
class AttentionNotificationService
{
    /**
     * A fresh teacher_attention row is written at most once per 24 hours per
     * student, regardless of how the state changed. A genuine change inside
     * the window is delayed until the cooldown clears, not lost.
     */
    public const COOLDOWN_HOURS = 24;

    public function __construct(
        private readonly AttentionService $attention,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Sync the requesting teacher's notification center with the current
     * needs-attention list: write a teacher_attention row for every student
     * whose ordered signal set differs from the teacher's latest stored set,
     * subject to the 24-hour cooldown. The writes run inside one transaction;
     * returns the number of rows created.
     */
    public function syncFor(User $teacher): int
    {
        return DB::transaction(function () use ($teacher): int {
            $latestByStudent = $this->latestAttentionByStudent($teacher);
            $created = 0;

            foreach ($this->attention->list() as $row) {
                $student = $row['student'];
                $signals = $row['signals'];

                $latest = $latestByStudent[$student->id] ?? null;

                if ($latest === null) {
                    $this->createFor($teacher, $row, $signals);
                    $created++;

                    continue;
                }

                $data = $latest->data;
                $latestSignals = is_array($data) ? ($data['signals'] ?? null) : null;

                if (is_array($latestSignals) && $latestSignals === $signals) {
                    continue;
                }

                if ($latest->created_at->greaterThan(now()->subHours(self::COOLDOWN_HOURS))) {
                    continue;
                }

                $this->createFor($teacher, $row, $signals);
                $created++;
            }

            return $created;
        });
    }

    /**
     * @param  array{
     *     student: User,
     *     current_course: Course,
     *     signals: non-empty-array<int, string>,
     *     primary: string,
     *     reasons: array<string, string>,
     *     last_activity_at: CarbonInterface|null,
     * }  $row
     * @param  non-empty-array<int, string>  $signals
     */
    private function createFor(User $teacher, array $row, array $signals): void
    {
        $student = $row['student'];
        $label = AttentionService::SIGNAL_LABELS[$row['primary']] ?? strtoupper($row['primary']);
        $reason = $row['reasons'][$row['primary']] ?? '';

        $this->notifications->create(
            $teacher,
            NotificationService::TYPE_TEACHER_ATTENTION,
            strtoupper($student->username).' NEEDS ATTENTION',
            "{$label}: {$reason}",
            null,
            NotificationService::payload(
                'needs-attention',
                [],
                ['student' => $student->id, 'signals' => $signals],
            ),
        );
    }

    /**
     * The teacher's newest teacher_attention row per student, keyed by
     * student id. One bounded query for the requesting user — no per-student
     * fan-out, matching the fleet-pages discipline (US-602/608).
     *
     * @return array<int, Notification>
     */
    private function latestAttentionByStudent(User $teacher): array
    {
        $rows = Notification::query()
            ->where('user_id', $teacher->id)
            ->where('type', NotificationService::TYPE_TEACHER_ATTENTION)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $latest = [];

        foreach ($rows as $row) {
            $data = $row->data;
            $studentId = is_array($data) ? ($data['student'] ?? null) : null;

            if (! (is_int($studentId) || (is_string($studentId) && is_numeric($studentId)))) {
                continue;
            }

            $key = (int) $studentId;

            if (! array_key_exists($key, $latest)) {
                $latest[$key] = $row;
            }
        }

        return $latest;
    }
}
