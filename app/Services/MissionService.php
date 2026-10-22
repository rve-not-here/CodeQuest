<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        private readonly XpService $xp,
        private readonly DraftService $drafts,
        private readonly ActivityService $activity,
        private readonly AchievementService $achievements,
        private readonly AssessmentService $assessments,
        private readonly NotificationService $notifications,
        private readonly MissionGradingService $grading,
        private readonly KnowledgeCheckGate $knowledgeCheckGate,
    ) {}

    /**
     * Validate a submission and resolve the outcome.
     *
     * @return array{
     *     passed: bool,
     *     alreadyCompleted: bool,
     *     failures: list<string>,
     *     xpAwarded: int,
     *     xpBalance: int
     * }
     */
    public function submit(User $user, Mission $mission, string $code, ?string $submissionId = null): array
    {
        if ($submissionId !== null && ! Str::isUuid($submissionId)) {
            throw ValidationException::withMessages(['submission_id' => 'Invalid submission identifier.']);
        }

        return DB::transaction(function () use ($user, $mission, $code, $submissionId): array {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $mission = Mission::query()->whereKey($mission->id)->sharedLock()->firstOrFail();
            $course = Course::query()->whereKey($mission->course_id)->sharedLock()->firstOrFail();
            $mission->setRelation('course', $course);

            abort_unless($course->status === 'active', 403);

            // Domain-level prerequisite: the controller checks first for a
            // friendly redirect, but the rule must also hold for any direct
            // service caller. Already-completed missions are exempt.
            $this->knowledgeCheckGate->assertCleared($user, $mission);

            if ($this->isCompleted($user, $mission)) {
                return [
                    'passed' => true,
                    'alreadyCompleted' => true,
                    'failures' => [],
                    'xpAwarded' => 0,
                    'xpBalance' => $this->xp->balance($user),
                ];
            }

            if (! Schema::hasTable('the404_mission_submission_results')) {
                return [
                    'passed' => false,
                    'alreadyCompleted' => false,
                    'failures' => ['Challenge submission is temporarily unavailable. Please try again later.'],
                    'xpAwarded' => 0,
                    'xpBalance' => $this->xp->balance($user),
                ];
            }

            $sourceHash = hash('sha256', $code);
            $operationId = $submissionId ?? hash('sha256', "legacy:{$mission->id}:{$mission->version}:{$sourceHash}");
            $stored = DB::table('the404_mission_submission_results')
                ->where('user_id', $user->id)->where('submission_id', $operationId)->first();

            if ($stored !== null) {
                if ((int) $stored->mission_id !== $mission->id || ! hash_equals($stored->source_hash, $sourceHash)) {
                    throw ValidationException::withMessages(['submission_id' => 'This submission identifier already belongs to different work.']);
                }
                $failures = json_decode($stored->failures, true, flags: JSON_THROW_ON_ERROR);
                if (! is_array($failures) || ! array_is_list($failures) || array_filter($failures, fn (mixed $failure): bool => ! is_string($failure)) !== []) {
                    throw new \RuntimeException('Stored submission outcome is invalid.');
                }

                return [
                    'passed' => false,
                    'alreadyCompleted' => false,
                    'failures' => $failures,
                    'xpAwarded' => 0,
                    'xpBalance' => $this->xp->balance($user),
                ];
            }

            $cacheable = true;
            $outcome = $this->submitForLockedUser($user, $mission, $code, $cacheable);
            if (! $outcome['passed'] && $cacheable) {
                DB::table('the404_mission_submission_results')->insert([
                    'user_id' => $user->id,
                    'mission_id' => $mission->id,
                    'mission_version' => $mission->version,
                    'submission_id' => $operationId,
                    'source_hash' => $sourceHash,
                    'failures' => json_encode($outcome['failures'], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                ]);
            }

            return $outcome;
        }, attempts: 3);
    }

    /**
     * @return array{passed: bool, alreadyCompleted: bool, failures: list<string>, xpAwarded: int, xpBalance: int}
     */
    private function submitForLockedUser(User $user, Mission $mission, string $code, bool &$cacheable): array
    {
        $result = $this->grading->grade($user, $mission, $code);

        if (! $result['passed']) {
            if ($result['unavailable']) {
                $cacheable = false;
                $this->activity->record($user, [
                    'type' => 'grading_unavailable',
                    'mission_id' => $mission->id,
                    'message' => 'Behavioral grading unavailable on challenge: '.$mission->title,
                ]);

                return [
                    'passed' => false,
                    'alreadyCompleted' => false,
                    'failures' => $result['failures'],
                    'xpAwarded' => 0,
                    'xpBalance' => $this->xp->balance($user),
                ];
            }

            $penalty = $this->xp->deductWrongSubmission($user, $mission);
            $this->activity->record($user, [
                'type' => 'wrong_submission',
                'mission_id' => $mission->id,
                'message' => 'Wrong submission on challenge: '.$mission->title,
                // The ledger clamps the penalty to the available balance, so
                // the activity row carries what was actually deducted — the
                // timeline must never claim −10 XP that was not charged.
                'pts' => $penalty > 0 ? -$penalty : 0,
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
            'Challenge completed',
            "Challenge completed: {$mission->title}",
            "mission_completed:{$mission->id}",
            NotificationService::payload('mission.show', ['mission' => $mission->id]),
        );

        if ($course !== null && ! $wasUnlocked && $this->assessments->isUnlocked($user, $course)) {
            $assessment = $this->assessments->forCourse($course);

            if ($assessment !== null) {
                $this->notifications->create(
                    $user,
                    NotificationService::TYPE_ASSESSMENT_UNLOCKED,
                    'Challenge unlocked',
                    "Boss Challenge unlocked: {$assessment->title}",
                    "assessment_unlocked:{$assessment->id}",
                    NotificationService::payload('assessment.show', ['assessment' => $assessment->id]),
                );
            }
        }

        $this->drafts->delete($user, $mission);

        $this->activity->record($user, [
            'type' => 'mission_completed',
            'mission_id' => $mission->id,
            'message' => 'Challenge completed: '.$mission->title,
            'pts' => $mission->points,
        ]);

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
