<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates the challenge submit flow end to end.
 *
 * Submit -> server validation -> on success: award XP, record progress,
 * clear the draft, log activity. On failure: deduct the wrong-submission
 * penalty and keep the draft. All multi-row writes run in a transaction.
 * Completion is idempotent: a mission already completed is reported as
 * already passed and never re-awarded.
 */
class MissionService
{
    public function __construct(
        private readonly ValidationService $validator,
        private readonly XpService $xp,
        private readonly DraftService $drafts,
        private readonly ActivityService $activity,
        private readonly AchievementService $achievements,
        private readonly AssessmentService $assessments,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Validate a submission and resolve the outcome.
     *
     * @return array{
     *     passed: bool,
     *     alreadyCompleted: bool,
     *     failures: array<int, string>,
     *     xpAwarded: int,
     *     xpBalance: int
     * }
     */
    public function submit(User $user, Mission $mission, string $code): array
    {
        if ($this->isCompleted($user, $mission)) {
            return [
                'passed' => true,
                'alreadyCompleted' => true,
                'failures' => [],
                'xpAwarded' => 0,
                'xpBalance' => $this->xp->balance($user),
            ];
        }

        $result = $this->validator->validate($mission, $code);

        if (! $result['passed']) {
            $this->xp->deductWrongSubmission($user, $mission);
            $this->activity->record($user, [
                'type' => 'wrong_submission',
                'message' => 'Wrong submission on mission: '.$mission->title,
            ]);

            return [
                'passed' => false,
                'alreadyCompleted' => false,
                'failures' => $result['failures'],
                'xpAwarded' => 0,
                'xpBalance' => $this->xp->balance($user),
            ];
        }

        $course = $mission->course;

        // Captured BEFORE the Progress row is written: whether the Boss
        // Challenge was already unlocked for this course. The unlock
        // announcement fires only when this flips false -> true (§19), not on
        // steady-state completions.
        $wasUnlocked = $course !== null && $this->assessments->isUnlocked($user, $course);

        DB::transaction(function () use ($user, $mission, $course, $wasUnlocked): void {
            $this->xp->awardCompletion($user, $mission);

            Progress::query()->create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'mission_version' => $mission->version,
                // Skill keys live at completion time: a later remapping must
                // never reinterpret this row (US-905/US-906).
                'skill_keys' => $mission->skills->pluck('key')->all(),
                'pts_earned' => $mission->points,
                'completed_at' => now(),
            ]);

            $this->achievements->evaluateMissionCompletion($user);

            // US-804: the mission-completed notification is created in the
            // SAME transaction as the Progress row (§19); a rollback removes
            // both. US-805: when this completion is what unlocks the course's
            // Boss Challenge (transition, not steady state), the student also
            // hears that the challenge is open.
            $this->notifications->create(
                $user,
                NotificationService::TYPE_MISSION_COMPLETED,
                'MISSION COMPLETED',
                "Mission completed: {$mission->title}",
                "mission_completed:{$mission->id}",
                NotificationService::payload('mission.show', ['mission' => $mission->id]),
            );

            if ($course !== null && ! $wasUnlocked && $this->assessments->isUnlocked($user, $course)) {
                $assessment = $this->assessments->forCourse($course);

                if ($assessment !== null) {
                    $this->notifications->create(
                        $user,
                        NotificationService::TYPE_ASSESSMENT_UNLOCKED,
                        'CHALLENGE UNLOCKED',
                        "Boss Challenge unlocked: {$assessment->title}",
                        "assessment_unlocked:{$assessment->id}",
                        NotificationService::payload('assessment.show', ['assessment' => $assessment->id]),
                    );
                }
            }

            $this->drafts->delete($user, $mission);

            $this->activity->record($user, [
                'type' => 'mission_completed',
                'message' => 'Mission completed: '.$mission->title,
                'pts' => $mission->points,
            ]);
        });

        return [
            'passed' => true,
            'alreadyCompleted' => false,
            'failures' => [],
            'xpAwarded' => $mission->points,
            'xpBalance' => $this->xp->balance($user),
        ];
    }

    public function isCompleted(User $user, Mission $mission): bool
    {
        return Progress::query()
            ->where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->exists();
    }
}
