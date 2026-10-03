<?php

namespace App\Services;

use App\Exceptions\AssessmentAlreadyExistsException;
use App\Exceptions\AssessmentAttemptAccessDeniedException;
use App\Exceptions\AssessmentAttemptStateException;
use App\Exceptions\AssessmentNotUnlockedException;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Owns the assessment domain's write path. The one-per-course invariant is
 * enforced here, at the application layer, so a duplicate assessment fails
 * with a clear domain error rather than a raw database unique-constraint
 * violation bubbling up to the caller.
 */
class AssessmentService
{
    public function __construct(
        private readonly ValidationService $validator,
        private readonly XpService $xp,
        private readonly AchievementService $achievements,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Create the single Boss Challenge for a course.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createForCourse(Course $course, array $attributes): Assessment
    {
        if ($this->existsForCourse($course)) {
            throw AssessmentAlreadyExistsException::forCourse($course->id);
        }

        return Assessment::query()->create([
            'course_id' => $course->id,
            ...$attributes,
        ]);
    }

    public function existsForCourse(Course $course): bool
    {
        return Assessment::query()
            ->where('course_id', $course->id)
            ->exists();
    }

    public function forCourse(Course $course): ?Assessment
    {
        return Assessment::query()
            ->where('course_id', $course->id)
            ->first();
    }

    /**
     * Batched read companions for the per-course assessment predicates below
     * (US-911). Fleet and overview paths aggregate in SQL and consume these
     * sets instead of issuing one lookup per course; the predicates stay
     * verbatim: an assessment belongs to the course holding its course_id
     * (unique per course), a pass is a 'passed' attempt row owned by the
     * student, eligibility is every course mission completed with at least
     * one mission present.
     *
     * @param  Collection<int, int>  $courseIds
     * @return Collection<int, int> course_id => assessment_id
     */
    public function assessmentIdsByCourse(Collection $courseIds): Collection
    {
        if ($courseIds->isEmpty()) {
            return collect();
        }

        return Assessment::query()
            ->whereIn('course_id', $courseIds)
            ->pluck('id', 'course_id');
    }

    /**
     * @return Collection<int, int> assessment ids with at least one passed attempt by the user
     */
    public function passedAssessmentIds(User $user): Collection
    {
        return AssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'passed')
            ->distinct()
            ->pluck('assessment_id');
    }

    /**
     * Per-course assessment state for a set of courses in a constant number
     * of queries: the assessment row, the hasPassed() verdict, and the
     * isEligible() verdict, each computed from the same predicates as the
     * single-course methods.
     *
     * @param  Collection<int, Course>  $courses
     * @return array<int, array{assessment: ?Assessment, passed: bool, eligible: bool, reached: bool, unlocked: bool, reason: string}> keyed by course id
     */
    public function assessmentStatesForCourses(User $user, Collection $courses): array
    {
        if ($courses->isEmpty()) {
            return [];
        }

        $courseIds = $courses->pluck('id');

        $assessments = Assessment::query()
            ->whereIn('course_id', $courseIds)
            ->get()
            ->keyBy('course_id');

        $missionIdsByCourse = Mission::query()
            ->whereIn('course_id', $courseIds)
            ->get(['id', 'course_id'])
            ->groupBy('course_id');

        $completed = Progress::query()
            ->where('user_id', $user->id)
            ->whereIn('mission_id', $missionIdsByCourse->flatten()->pluck('id'))
            ->pluck('mission_id')
            ->flip();

        $passed = $this->passedAssessmentIds($user)->flip();
        $reached = $this->courseReachForCourses($user, $courses);

        $states = [];

        foreach ($courses as $course) {
            $missionIds = $missionIdsByCourse->get($course->id, collect())->pluck('id')->all();
            $assessment = $assessments->get($course->id);
            $eligible = $missionIds !== [] && collect($missionIds)->every(fn (int $id): bool => $completed->has($id));
            $unlocked = $course->status === 'active' && $reached[$course->id]
                && $assessment?->status === 'active' && $eligible;

            $states[$course->id] = [
                'assessment' => $assessment,
                'passed' => $assessment !== null && $passed->has($assessment->id),
                'eligible' => $eligible,
                'reached' => $reached[$course->id],
                'unlocked' => $unlocked,
                'reason' => match (true) {
                    $course->status !== 'active' => 'This course is not currently active.',
                    ! $reached[$course->id] => 'Pass earlier courses before opening this course.',
                    $assessment === null => 'No Boss Challenge is configured for this course.',
                    $assessment->status !== 'active' => 'This assessment is not currently active.',
                    ! $eligible => 'Complete every required challenge before opening this challenge.',
                    default => 'All required challenges are complete.',
                },
            ];
        }

        return $states;
    }

    /**
     * Whether a student may attempt the course's Boss Challenge (§5.1).
     *
     * A course is assessment-eligible only when the student has completed
     * every mission in the course, reasoned through the real course→section→
     * mission hierarchy (actual per-mission Progress rows, not a flat counter).
     * A course with zero missions is never eligible. Server-determined.
     */
    public function isEligible(User $user, Course $course): bool
    {
        $missionIds = $course->missions()->pluck('id');

        if ($missionIds->isEmpty()) {
            return false;
        }

        $completedIds = Progress::query()
            ->where('user_id', $user->id)
            ->whereIn('mission_id', $missionIds)
            ->pluck('mission_id');

        return $completedIds->count() === $missionIds->count();
    }

    /**
     * Earlier active courses with missions must have a historical Boss
     * Challenge pass before a student can work in this course.
     */
    public function isCourseReached(User $user, Course $course): bool
    {
        return $this->courseReachForCourses($user, collect([$course]))[$course->id];
    }

    /**
     * @param  Collection<int, Course>  $courses
     * @return array<int, bool>
     */
    public function courseReachForCourses(User $user, Collection $courses): array
    {
        if ($courses->isEmpty()) {
            return [];
        }

        $blockingCourses = Course::query()
            ->where('status', 'active')
            ->whereHas('missions')
            ->whereDoesntHave('assessment.attempts', fn ($query) => $query->where('user_id', $user->id)->where('status', 'passed'))
            ->get(['id', 'order_num']);

        $reached = [];
        foreach ($courses as $course) {
            $reached[$course->id] = ! $blockingCourses->contains(fn (Course $blocking): bool => $blocking->order_num < $course->order_num
                || ($blocking->order_num === $course->order_num && $blocking->id < $course->id));
        }

        return $reached;
    }

    /**
     * Whether the course's Boss Challenge is unlocked for the student (§6).
     *
     * Unlocking is server-authoritative and requires all of: the COURSE is
     * status "active" (not locked/draft — course.status is an access gate,
     * US-705 confirmed), the course has an assessment, that assessment is
     * status "active", and the student is eligible (US-403). The frontend may
     * display this state but never create or modify it.
     */
    public function isUnlocked(User $user, Course $course): bool
    {
        if ($course->status !== 'active' || ! $this->isCourseReached($user, $course)) {
            return false;
        }

        $assessment = $this->forCourse($course);

        if ($assessment === null || $assessment->status !== 'active') {
            return false;
        }

        return $this->isEligible($user, $course);
    }

    /**
     * Whether the student has ever passed the course's Boss Challenge (US-410).
     *
     * "Course complete" keys off attempt history: the existence of a "passed"
     * row for this assessment/user, not the latest verdict. Because
     * retry-after-pass is allowed (US-409), the newest attempt may later come
     * back "failed"; this predicate reads history so a failed retry never
     * un-completes a course (confirmed decision). Course-completion, next-
     * course unlocking, and assessment XP must all key off this, not
     * latestAttemptFor().
     */
    public function hasPassed(User $user, Course $course): bool
    {
        $assessment = $this->forCourse($course);

        if ($assessment === null) {
            return false;
        }

        return $this->attempts($assessment, $user)
            ->where('status', 'passed')
            ->exists();
    }

    /**
     * How many Boss Challenges the student has ever passed (the dashboard's
     * "Learning record"). Counts distinct assessments with at least one
     * "passed" attempt row — retry attempts are not counted twice (US-409).
     */
    public function passedCount(User $user): int
    {
        return (int) AssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'passed')
            ->distinct()
            ->count('assessment_id');
    }

    /**
     * Begin the Boss Challenge for a student (§US-405).
     *
     * One row drives one attempt lifecycle: a fresh row is created in the
     * "started" state, or an existing "available" row is transitioned to
     * "started"; an already-started attempt is returned unchanged. Only the
     * owner's eligibility unlocks beginning (US-403/US-404). Beginning against
     * a submitted/evaluated/passed/failed attempt is refused; retryAttempt()
     * (US-409) is the path that opens a fresh row after a terminal outcome.
     */
    public function beginAttempt(User $user, Course $course): AssessmentAttempt
    {
        return DB::transaction(function () use ($user, $course): AssessmentAttempt {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (! $this->isUnlocked($user, $course)) {
                throw AssessmentNotUnlockedException::forCourse($course->id);
            }

            $assessment = $this->forCourse($course);

            if ($assessment === null) {
                throw new \InvalidArgumentException('Course '.$course->id.' has no assessment.');
            }

            $attempt = $this->attempts($assessment, $user)->first();

            if ($attempt === null) {
                $attempt = new AssessmentAttempt([
                    'assessment_id' => $assessment->id,
                    'user_id' => $user->id,
                    // Skill keys live at attempt start for future mapping use.
                    // v1 skill scoring ignores Boss evidence entirely.
                    'skill_keys' => $assessment->skills->pluck('key')->all(),
                ]);

                $attempt->status = 'started';
                $attempt->assessment_version = $assessment->version;
                $attempt->save();

                return $attempt;
            }

            if ($attempt->status === 'available') {
                $attempt->status = 'started';
                $attempt->save();

                return $attempt;
            }

            if ($attempt->status === 'started') {
                return $attempt;
            }

            throw AssessmentAttemptStateException::mismatch($attempt->id, ['available', 'started'], $attempt->status);
        }, attempts: 3);
    }

    /**
     * The student's most recent attempt at a course's Boss Challenge, if any.
     */
    public function latestAttemptFor(User $user, Course $course): ?AssessmentAttempt
    {
        $assessment = $this->forCourse($course);

        if ($assessment === null) {
            return null;
        }

        return $this->attempts($assessment, $user)
            ->latest('id')
            ->first();
    }

    /**
     * Latest attempt per assessment for one student, using only fields the
     * assessment hub needs. Historical attempts and submitted code stay out
     * of the returned collection.
     *
     * @param  Collection<int, int>  $assessmentIds
     * @return Collection<int, AssessmentAttempt> keyed by assessment id
     */
    public function latestAttemptsForAssessments(User $user, Collection $assessmentIds): Collection
    {
        if ($assessmentIds->isEmpty()) {
            return collect();
        }

        $latestIds = AssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('assessment_id', $assessmentIds)
            ->selectRaw('MAX(id) AS id')
            ->groupBy('assessment_id')
            ->pluck('id');

        return AssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $latestIds)
            ->get(['id', 'assessment_id', 'status'])
            ->keyBy('assessment_id');
    }

    /**
     * Read-only attempt history for a student's Boss Challenge (US-604).
     *
     * The teacher-facing performance view reuses this instead of querying
     * AssessmentAttempt with fresh logic: attempt scoping stays inside the
     * service, so every reader keys off the same history the domain owns.
     * Attempts are returned newest first, projected to what a reader may see
     * (verdict, score, timestamps) — the student's submitted code is never
     * exposed, and nothing in this method writes. A course with no assessment
     * (and no conceivable attempt) yields an empty collection.
     *
     * @return Collection<int, array{
     *     id: int,
     *     status: string,
     *     score: int|null,
     *     passed_at: Carbon|null,
     *     submitted_at: Carbon|null,
     * }>
     */
    public function attemptHistory(User $user, Course $course): Collection
    {
        $assessment = $this->forCourse($course);

        if ($assessment === null) {
            return collect();
        }

        return $this->attempts($assessment, $user)
            ->orderByDesc('id')
            ->get()
            ->map(fn (AssessmentAttempt $attempt): array => $this->presentAttempt($attempt));
    }

    /**
     * Attempt histories for many courses in two queries, for fleet and
     * overview paths (US-911). Same rows, order, and shape as calling
     * attemptHistory() per course; courses without attempts are simply
     * absent from the map.
     *
     * @param  Collection<int, Course>  $courses
     * @return Collection<int, Collection<int, array{
     *     id: int,
     *     status: string,
     *     score: int|null,
     *     passed_at: Carbon|null,
     *     submitted_at: Carbon|null,
     * }>>  keyed by course id
     */
    public function attemptHistoriesForCourses(User $user, Collection $courses): Collection
    {
        $aidsByCourse = $this->assessmentIdsByCourse($courses->pluck('id'));

        if ($aidsByCourse->isEmpty()) {
            return collect();
        }

        $grouped = AssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('assessment_id', $aidsByCourse->values())
            ->orderByDesc('id')
            ->get()
            ->groupBy('assessment_id');

        $courseByAid = $aidsByCourse->flip();
        $histories = [];

        foreach ($grouped as $assessmentId => $attempts) {
            $courseId = $courseByAid->get($assessmentId);

            if ($courseId === null) {
                continue;
            }

            $histories[$courseId] = $attempts
                ->map(fn (AssessmentAttempt $attempt): array => $this->presentAttempt($attempt))
                ->values();
        }

        return collect($histories);
    }

    /**
     * @return array{
     *     id: int,
     *     status: string,
     *     score: int|null,
     *     passed_at: Carbon|null,
     *     submitted_at: Carbon|null,
     * }
     */
    private function presentAttempt(AssessmentAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'status' => $attempt->status,
            'score' => $attempt->score,
            'passed_at' => $attempt->passed_at,
            'submitted_at' => $attempt->submitted_at,
        ];
    }

    /**
     * Whether a user owns an attempt. Access to a student's submitted code
     * and score is reasoned explicitly here, not left to a view omitting other
     * users' records.
     */
    public function hasAccessToAttempt(User $user, AssessmentAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id;
    }

    /**
     * Ownership-gated serialization of an attempt. Returns the full record
     * (including code and score) only when the user owns the attempt, and
     * throws otherwise. Callers must not serialize attempts through a view
     * without passing through this gate.
     *
     * @return array<string, mixed>
     */
    public function attemptResult(User $user, AssessmentAttempt $attempt): array
    {
        if (! $this->hasAccessToAttempt($user, $attempt)) {
            throw AssessmentAttemptAccessDeniedException::forAttempt($attempt->id);
        }

        return [
            'id' => $attempt->id,
            'assessment_id' => $attempt->assessment_id,
            'status' => $attempt->status,
            'score' => $attempt->score,
            'code' => $attempt->code,
            'passed_at' => $attempt->passed_at?->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
        ];
    }

    /**
     * Commit the synchronous submission and verdict together. A recorded
     * submission is recoverable using its stored source, never replacement
     * source from a retry request.
     */
    public function submitAndEvaluateAttempt(User $user, AssessmentAttempt $attempt, string $code): AssessmentAttempt
    {
        if (! $this->hasAccessToAttempt($user, $attempt)) {
            throw AssessmentAttemptAccessDeniedException::forAttempt($attempt->id);
        }

        return DB::transaction(function () use ($user, $attempt, $code): AssessmentAttempt {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $current = AssessmentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if (! $this->hasAccessToAttempt($user, $current)) {
                throw AssessmentAttemptAccessDeniedException::forAttempt($current->id);
            }

            $course = $current->assessment?->course;

            if ($course === null || ! $this->isUnlocked($user, $course)) {
                throw AssessmentNotUnlockedException::forCourse($course->id ?? 0);
            }

            if ($current->status !== 'submitted') {
                $this->submitAttempt($user, $current, $code);
            }

            return $this->evaluateAttempt($user, $current);
        }, attempts: 3);
    }

    /**
     * Record a student's submission for an assessment attempt (§US-406).
     *
     * Accepts only the student's submission code, against an attempt they own
     * (§44). Ownership is checked before anything is written, so a client
     * that submits against another student's attempt is rejected outright.
     * The method has no score, pass/fail, XP, completion, or eligibility
     * parameters: none of those may come from the client in any form (§11,
     * §43, §45). This only records the submission; scoring the code and
     * deciding pass/fail belongs to evaluation (US-407).
     */
    public function submitAttempt(User $user, AssessmentAttempt $attempt, string $code): AssessmentAttempt
    {
        if (! $this->hasAccessToAttempt($user, $attempt)) {
            throw AssessmentAttemptAccessDeniedException::forAttempt($attempt->id);
        }

        DB::transaction(function () use ($user, $attempt, $code): void {
            $current = AssessmentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if (! $this->hasAccessToAttempt($user, $current)) {
                throw AssessmentAttemptAccessDeniedException::forAttempt($current->id);
            }

            if ($current->status !== 'started') {
                throw AssessmentAttemptStateException::mismatch($current->id, ['started'], $current->status);
            }

            $current->code = $code;
            $current->status = 'submitted';
            $current->submitted_at = now();
            $current->save();
        }, attempts: 3);

        return $attempt->refresh();
    }

    /**
     * Evaluate a submitted attempt against its assessment's grading_rule
     * (US-407).
     *
     * Scoring approach: the grading_rule is a set of binary pass/fail pattern
     * rules with no weights, so the score is the proportion of rules passed,
     * rounded to an integer percentage and compared against passing_score.
     * A submission with no rules scores 0 and does not pass, rather than
     * auto-passing, because a Boss Challenge must never pass vacuously.
     *
     * The code is never executed. It is matched as literal text against the
     * grading_rule patterns by ValidationService, exactly as Phase 3 matches
     * missions; there is no eval/exec or server-side JS interpreter. The
     * attempt must be owned by the user and be in the "submitted" state.
     * On pass the attempt is moved directly to "passed" with passed_at; on
     * fail, directly to "failed". "evaluated" is not persisted on this path.
     *
     * The Boss Challenge XP reward (US-412) is written here, atomically with
     * the verdict, and only when evaluation produced the student's FIRST
     * pass (a passed attempt predating this one). A repeat pass via retry
     * (US-409) therefore never re-awards, and a failed retry never reverses
     * the original award.
     */
    public function evaluateAttempt(User $user, AssessmentAttempt $attempt): AssessmentAttempt
    {
        if (! $this->hasAccessToAttempt($user, $attempt)) {
            throw AssessmentAttemptAccessDeniedException::forAttempt($attempt->id);
        }

        if ($attempt->assessment === null) {
            throw new \InvalidArgumentException('Assessment attempt '.$attempt->id.' references no assessment.');
        }

        DB::transaction(function () use ($user, $attempt): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $attempt = AssessmentAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if (! $this->hasAccessToAttempt($user, $attempt)) {
                throw AssessmentAttemptAccessDeniedException::forAttempt($attempt->id);
            }

            if ($attempt->status !== 'submitted') {
                throw AssessmentAttemptStateException::mismatch($attempt->id, ['submitted'], $attempt->status);
            }

            $assessment = $attempt->assessment;

            if ($assessment === null) {
                throw new \InvalidArgumentException('Assessment attempt '.$attempt->id.' references no assessment.');
            }

            $result = $this->validator->validateRules($assessment->grading_rule ?? '', $attempt->code ?? '');

            $total = $result['total'];
            // Coupling: ValidationService::validateRules() folds a malformed rule
            // entry into the failures list, so it is counted here as a failed rule.
            // That is currently safe only because seeded grading_rule content is
            // assumed valid; a broken rule would otherwise score as a student
            // failure. Guarded, not fixed: recording malformed-rule credit is a
            // US-407 design decision we are not changing here.
            $failed = count($result['failures']);

            $score = $total > 0 ? (int) round((($total - $failed) / $total) * 100) : 0;

            $passed = $total > 0 && $score >= $assessment->passing_score;

            // The pre-state, read before this attempt is written as passed: has
            // the student already earned a passed verdict on this assessment?
            // Mirrors hasPassed() and feeds the award-only-on-first-pass rule.
            $hadPassedBefore = $this->attempts($assessment, $user)
                ->where('status', 'passed')
                ->exists();

            $attempt->score = $score;
            $attempt->status = $passed ? 'passed' : 'failed';
            $attempt->passed_at = $passed ? now() : null;
            // Evidence of the exact revision that produced this verdict: the
            // assessment version in force at evaluation time plus a copy of
            // the rule and threshold it was scored against. Later edits to
            // grading_rule or passing_score never rewrite these columns.
            $attempt->assessment_version = $assessment->version;
            $attempt->grading_rule_snapshot = $assessment->grading_rule;
            $attempt->passing_score_snapshot = $assessment->passing_score;
            $attempt->save();

            // US-805: the verdict notification is created in the SAME
            // transaction as the verdict (§19) — a retry pass/fail is its own
            // attempt id, so each evaluation announces itself exactly once.
            if ($passed) {
                $this->notifications->create(
                    $user,
                    NotificationService::TYPE_ASSESSMENT_PASSED,
                    'CHALLENGE PASSED',
                    "Boss Challenge passed: {$assessment->title}",
                    "assessment_passed:{$attempt->id}",
                    NotificationService::payload('assessment.show', ['assessment' => $assessment->id]),
                );
            } else {
                $this->notifications->create(
                    $user,
                    NotificationService::TYPE_ASSESSMENT_FAILED,
                    'CHALLENGE FAILED',
                    "Boss Challenge failed: {$assessment->title}",
                    "assessment_failed:{$attempt->id}",
                    NotificationService::payload('assessment.show', ['assessment' => $assessment->id]),
                );
            }

            if ($passed && ! $hadPassedBefore) {
                $this->xp->awardAssessmentPass($user, $assessment);
                $this->achievements->evaluateAssessmentPass($user);

                // US-804: only a FIRST pass completes the course and hands
                // over the next course; completion keys off the same history
                // predicate as hasPassed() (§4/§410), never the latest
                // verdict, so a retry never re-fires either event.
                $course = $assessment->course;

                if ($course !== null) {
                    $this->notifications->create(
                        $user,
                        NotificationService::TYPE_COURSE_COMPLETED,
                        'COURSE COMPLETED',
                        "Course cleared: {$course->name}",
                        "course_completed:{$course->id}",
                        NotificationService::payload('learning-path', ['course' => $course->id]),
                    );
                }

                $next = $this->firstNotPassedCourse($user);

                if ($next !== null) {
                    $this->notifications->create(
                        $user,
                        NotificationService::TYPE_NEXT_COURSE_UNLOCKED,
                        'NEXT COURSE UNLOCKED',
                        "Next course unlocked: {$next->name}",
                        "next_course_unlocked:{$next->id}",
                        NotificationService::payload('learning-path', ['course' => $next->id]),
                    );
                }
            }

        }, attempts: 3);

        return $attempt->refresh();
    }

    /**
     * Open a fresh attempt after a terminal outcome (US-409).
     *
     * Per §17 a failed attempt stays retryable with no invented limits,
     * cooldowns, or approval gates, and per the confirmed decision a retry is
     * also allowed after passing. A retry is a new row: the previous attempt
     * is left untouched as history, and the new attempt starts blank ("started"
     * with no code), keyed off the LATEST attempt's status. Retrying while an
     * attempt is active ('available'/'started'/'submitted') is refused, and
     * retrying with no prior attempt is refused (that is a begin).
     */
    public function retryAttempt(User $user, Course $course): AssessmentAttempt
    {
        return DB::transaction(function () use ($user, $course): AssessmentAttempt {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (! $this->isUnlocked($user, $course)) {
                throw AssessmentNotUnlockedException::forCourse($course->id);
            }

            $assessment = $this->forCourse($course);

            if ($assessment === null) {
                throw new \InvalidArgumentException('Course '.$course->id.' has no assessment.');
            }

            $latest = $this->latestAttemptFor($user, $course);

            if ($latest === null) {
                throw AssessmentAttemptStateException::noAttemptToRetry($course->id);
            }

            if (! in_array($latest->status, ['failed', 'passed'], true)) {
                throw AssessmentAttemptStateException::mismatch($latest->id, ['failed', 'passed'], $latest->status);
            }

            $retry = new AssessmentAttempt([
                'assessment_id' => $assessment->id,
                'user_id' => $user->id,
                'skill_keys' => $assessment->skills->pluck('key')->all(),
            ]);

            $retry->status = 'started';
            $retry->assessment_version = $assessment->version;
            $retry->save();

            return $retry;
        }, attempts: 3);
    }

    /**
     * The first active course, in progression order, whose Boss Challenge the
     * user has not reached first-pass completion on — run AFTER the attempt
     * row is saved as passed, so the just-completed course is skipped and the
     * next course in order_num surfaces (US-411: next-course unlock is
     * derived, never a stored row). Mirrors DashboardService::currentCourse,
     * which AssessmentService cannot inject without a cycle; the feature
     * tests tie the two observed results together.
     */
    private function firstNotPassedCourse(User $user): ?Course
    {
        return Course::query()
            ->where('status', 'active')
            ->orderBy('order_num')
            ->orderBy('id')
            ->withCount('missions')
            ->get()
            ->first(function (Course $course) use ($user): bool {
                if ($course->missions_count === 0) {
                    return false;
                }

                return ! $this->hasPassed($user, $course);
            });
    }

    /**
     * @return Builder<AssessmentAttempt>
     */
    private function attempts(Assessment $assessment, User $user): Builder
    {
        return AssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $user->id);
    }
}
