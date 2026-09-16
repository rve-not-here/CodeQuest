<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Progress;
use App\Models\User;
use App\Models\UserAchievement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Server-authoritative achievement unlocks (US-508).
 *
 * An achievement is a catalog row (seeded system content) and an award is a
 * (user, achievement) pair in the award ledger, unique at the database level.
 * There is no client-suppliable path to an unlock under any name: award() is
 * reached only from the two server-side trigger points below, and the check
 * runs at the moment of the triggering event, inside the same transaction
 * that records the event, so a failed award write rolls the event back.
 */
class AchievementService
{
    public const SLUG_FIRST_CHALLENGE = 'first_challenge';

    public const SLUG_FIRST_COURSE = 'first_course';

    public const SLUG_STREAK_3 = 'streak_3';

    public const SLUG_FULL_CLEAR = 'full_clear';

    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @return array<int, string>
     */
    public static function slugs(): array
    {
        return [
            self::SLUG_FIRST_CHALLENGE,
            self::SLUG_FIRST_COURSE,
            self::SLUG_STREAK_3,
            self::SLUG_FULL_CLEAR,
        ];
    }

    /**
     * A learning day counts toward the streak only on the day it happens.
     */
    public const STREAK_DAYS = 3;

    /**
     * Checks the mission-completion triggers. Called from inside the mission
     * completion transaction in MissionService, after the Progress row is
     * written, so this freshly completed mission is part of the counts read
     * here.
     */
    public function evaluateMissionCompletion(User $user): void
    {
        $completedCount = Progress::query()
            ->where('user_id', $user->id)
            ->count();

        if ($completedCount === 1) {
            $this->award($user, self::SLUG_FIRST_CHALLENGE);
        }

        if ($this->currentStreak($user) >= self::STREAK_DAYS) {
            $this->award($user, self::SLUG_STREAK_3);
        }
    }

    /**
     * Checks the Boss Challenge pass triggers. Called from inside the
     * evaluateAttempt transaction, in the first-pass branch, after the
     * attempt row is saved as passed, so this pass is part of the history
     * read here.
     */
    public function evaluateAssessmentPass(User $user): void
    {
        $passedAssessmentCount = AssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'passed')
            ->distinct()
            ->count('assessment_id');

        if ($passedAssessmentCount === 1) {
            $this->award($user, self::SLUG_FIRST_COURSE);
        }

        if ($this->allActiveCoursesPassed($user)) {
            $this->award($user, self::SLUG_FULL_CLEAR);
        }
    }

    /**
     * Consecutive calendar days on which the user completed at least one
     * mission, counted from the most recent completion day backwards.
     */
    public function currentStreak(User $user): int
    {
        $dates = Progress::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->selectRaw('DISTINCT DATE(completed_at) AS day')
            ->pluck('day')
            ->sortDesc();

        $streak = 0;
        $cursor = null;

        foreach ($dates as $day) {
            $date = Carbon::parse($day);

            if ($cursor === null || $date->eq($cursor)) {
                $streak++;

                $cursor = $date->copy()->subDay();

                continue;
            }

            break;
        }

        return $streak;
    }

    /**
     * Grant an achievement, idempotently. Returns false when the user already
     * holds it; the database unique (user, achievement) constraint backs the
     * guard. Throws when the catalog has no row for the slug: that is a
     * seeding error, and it rolls back the event transaction with a clear
     * message rather than silently skipping.
     */
    public function award(User $user, string $slug): bool
    {
        $achievement = Achievement::query()->where('slug', $slug)->first();

        if ($achievement === null) {
            throw new RuntimeException("Achievement catalog is missing slug '{$slug}'.");
        }

        return DB::transaction(function () use ($user, $achievement): bool {
            $exists = UserAchievement::query()
                ->where('user_id', $user->id)
                ->where('achievement_id', $achievement->id)
                ->exists();

            if ($exists) {
                return false;
            }

            UserAchievement::query()->create([
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
            ]);

            // US-804: the award announcement lands in the same transaction as
            // the UserAchievement row, so an event rollback removes both.
            $this->notifications->create(
                $user,
                NotificationService::TYPE_ACHIEVEMENT_EARNED,
                'ACHIEVEMENT EARNED',
                "Achievement unlocked: {$achievement->name}",
                "achievement_earned:{$achievement->id}",
                NotificationService::payload('achievements'),
            );

            return true;
        });
    }

    /**
     * The full catalog annotated with the user's award state. Display support
     * for the achievements page (still a standby shell); the award ledger
     * remains write-only through award().
     *
     * @return Collection<int, array{
     *     slug: string,
     *     name: string,
     *     description: ?string,
     *     awarded: bool,
     *     unlocked_at: ?Carbon,
     * }>
     */
    public function catalog(User $user): Collection
    {
        $awards = UserAchievement::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('achievement_id');

        return Achievement::query()
            ->orderBy('id')
            ->get()
            ->map(function (Achievement $achievement) use ($awards): array {
                $award = $awards->get($achievement->id);

                return [
                    'slug' => $achievement->slug,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'awarded' => $award !== null,
                    'unlocked_at' => $award === null ? null : Carbon::parse($award->created_at),
                ];
            });
    }

    /**
     * "Every active course's Boss Challenge passed" (§27, full_clear). The
     * eligible set is active courses that have at least one mission and an
     * assessment, mirroring the zero-mission/locked exclusion used by current
     * course resolution and competency: a course that can never be passed is
     * not part of the set, and an empty set never clears.
     */
    private function allActiveCoursesPassed(User $user): bool
    {
        $assessmentCourseIds = Assessment::query()->pluck('course_id');

        if ($assessmentCourseIds->isEmpty()) {
            return false;
        }

        $courses = Course::query()
            ->where('status', 'active')
            ->whereIn('id', $assessmentCourseIds)
            ->withCount('missions')
            ->get()
            ->filter(fn (Course $course): bool => $course->missions_count > 0);

        if ($courses->isEmpty()) {
            return false;
        }

        $requiredCourseIds = $courses->pluck('id');

        $passedCourseIds = Assessment::query()
            ->whereIn(
                'course_id',
                AssessmentAttempt::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'passed')
                    ->pluck('assessment_id')
            )
            ->pluck('course_id')
            ->unique();

        return $passedCourseIds->intersect($requiredCourseIds)->count() === $requiredCourseIds->count();
    }
}
