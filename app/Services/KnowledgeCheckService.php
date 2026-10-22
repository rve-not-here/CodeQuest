<?php

namespace App\Services;

use App\Exceptions\KnowledgeCheckAccessDeniedException;
use App\Exceptions\KnowledgeCheckStateException;
use App\Exceptions\KnowledgeCheckUnavailableException;
use App\Models\Course;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\KnowledgeCheckResponse;
use App\Models\Mission;
use App\Models\User;
use App\Support\KnowledgeCheckView;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KnowledgeCheckService
{
    public const ACTIVITY_TYPE_COMPLETED = 'knowledge_check_completed';

    /** Register states: the student's latest attempt decides the row. */
    public const STATE_NOT_STARTED = 'not_started';

    public const STATE_IN_PROGRESS = 'in_progress';

    public const STATE_COMPLETED = 'completed';

    public function __construct(
        private readonly ActivityService $activity,
        private readonly ClassroomAccessService $classroomAccess,
    ) {}

    /**
     * Immutable submitted evidence, restricted to current classroom pairs.
     * Unsubmitted attempts never expose questions or answer keys here.
     *
     * @return LengthAwarePaginator<int, KnowledgeCheckAttempt>
     */
    public function monitoringAttempts(User $viewer, User $student): LengthAwarePaginator
    {
        return $this->monitoringQuery($viewer, $student)
            ->with(['knowledgeCheck.mission.course', 'responses'])
            ->orderByDesc('submitted_at')->orderByDesc('id')
            ->paginate(20, ['*'], 'checks_page');
    }

    /**
     * Counts describe submitted responses, never competency or mastery.
     * Distinct historical prompts remain distinct after curriculum edits.
     *
     * @return Collection<int, array{prompt: string, responses: int, incorrect: int}>
     */
    public function monitoringMisconceptions(User $viewer, User $student): Collection
    {
        return KnowledgeCheckResponse::query()
            ->whereIn('knowledge_check_attempt_id', $this->monitoringQuery($viewer, $student)->select('id'))
            ->select('knowledge_check_question_id', 'prompt_snapshot')
            ->selectRaw('COUNT(*) AS response_count, SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) AS incorrect_count')
            ->groupBy('knowledge_check_question_id', 'prompt_snapshot')
            ->havingRaw('SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) > 0')
            ->orderByDesc('incorrect_count')->orderBy('knowledge_check_question_id')->limit(20)
            ->get()->map(fn (KnowledgeCheckResponse $response): array => $this->misconceptionRow($response->prompt_snapshot, (int) $response->getAttribute('response_count'), (int) $response->getAttribute('incorrect_count')));
    }

    /** @return array{prompt: string, responses: int, incorrect: int} */
    private function misconceptionRow(string $prompt, int $responses, int $incorrect): array
    {
        return ['prompt' => $prompt, 'responses' => $responses, 'incorrect' => $incorrect];
    }

    /** @return Builder<KnowledgeCheckAttempt> */
    private function monitoringQuery(User $viewer, User $student): Builder
    {
        $access = $this->classroomAccess;
        if (! in_array($viewer->role, ['teacher', 'admin'], true)
            || $student->role !== 'student' || ! $access->isAuthorizedForStudent($viewer, $student)) {
            throw new AuthorizationException;
        }
        $courseIds = $access->courseIdsForStudent($viewer, $student);

        return KnowledgeCheckAttempt::query()->where('user_id', $student->id)
            ->where('status', KnowledgeCheckAttempt::STATUS_SUBMITTED)
            ->when($courseIds !== null, fn (Builder $query) => $query->whereHas('knowledgeCheck.mission', fn (Builder $query) => $query->whereIn('course_id', $courseIds)));
    }

    /**
     * The Knowledge Check panels of one mission, for the mission workspace.
     *
     * Same published/attempt state as the register, plus the questions of an
     * open attempt so the student can answer inside the mission tab. The
     * question projection is the one the attempt screen uses: prompt, code
     * snippet and option text only — never the answer key.
     *
     * @return Collection<int, array{
     *     check: KnowledgeCheckView,
     *     questionCount: int,
     *     required: bool,
     *     state: string,
     *     attempt: array{id: int, attempt_number: int, score: int|null, total_questions: int|null, percentage: int|null}|null,
     *     questions: list<array{id: int, type: string, prompt: string, codeSnippet: string|null, options: list<array{id: int, text: string}>}>,
     * }>
     */
    public function missionPanels(User $user, Mission $mission): Collection
    {
        return $this->lessonSummaries($user, $mission)->map(function (array $summary) use ($user, $mission): array {
            $check = $summary['check'];
            $attempt = $summary['latestAttempt'];

            $state = match ($attempt?->status) {
                KnowledgeCheckAttempt::STATUS_SUBMITTED => self::STATE_COMPLETED,
                KnowledgeCheckAttempt::STATUS_STARTED => self::STATE_IN_PROGRESS,
                default => self::STATE_NOT_STARTED,
            };

            $questions = [];

            if ($attempt !== null && $attempt->status === KnowledgeCheckAttempt::STATUS_STARTED) {
                $questions = array_values($this->presentation($user, $mission, $check, $attempt)['questions']
                    ->map(fn (array $question): array => [
                        'id' => (int) $question['id'],
                        'type' => (string) $question['type'],
                        'prompt' => (string) $question['prompt'],
                        'codeSnippet' => $question['codeSnippet'] === null ? null : (string) $question['codeSnippet'],
                        'options' => array_values($question['options']->map(fn (array $option): array => [
                            'id' => (int) $option['id'],
                            'text' => (string) $option['text'],
                        ])->all()),
                    ])
                    ->all());
            }

            return [
                'check' => KnowledgeCheckView::from($check),
                'questionCount' => (int) $summary['questionCount'],
                'required' => (bool) $check->is_required,
                'state' => $state,
                'attempt' => $attempt === null ? null : [
                    'id' => (int) $attempt->id,
                    'attempt_number' => (int) $attempt->attempt_number,
                    'score' => $attempt->score === null ? null : (int) $attempt->score,
                    'total_questions' => $attempt->total_questions === null ? null : (int) $attempt->total_questions,
                    'percentage' => $attempt->percentage === null ? null : (int) $attempt->percentage,
                ],
                'questions' => $questions,
            ];
        });
    }

    /**
     * Presentation-ready check status for one Lesson. The latest attempt is
     * shown; prior attempts remain immutable history.
     *
     * @return Collection<int, array{
     *     check: KnowledgeCheck,
     *     questionCount: int<0, max>,
     *     latestAttempt: KnowledgeCheckAttempt|null,
     * }>
     */
    public function lessonSummaries(User $user, Mission $mission): Collection
    {
        $checks = $mission->knowledgeChecks()
            ->where('status', KnowledgeCheck::STATUS_PUBLISHED)
            ->withCount('questions')
            ->get();

        if ($checks->isEmpty()) {
            return collect();
        }

        $latestAttempts = KnowledgeCheckAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('knowledge_check_id', $checks->pluck('id'))
            ->orderByDesc('attempt_number')
            ->get()
            ->groupBy('knowledge_check_id')
            ->map(fn (Collection $attempts): ?KnowledgeCheckAttempt => $attempts->first());

        return $checks->map(fn (KnowledgeCheck $check): array => [
            'check' => $check,
            'questionCount' => (int) $check->questions_count,
            'latestAttempt' => $latestAttempts->get($check->id),
        ]);
    }

    /**
     * Per-course Knowledge Check evidence for the performance register.
     *
     * A check counts as submitted only when the student has a submitted
     * attempt; `correct`/`questions` come from the latest submitted attempt
     * per check (retries replace earlier evidence, never stack), so the
     * denominator is exactly the questions the student has answered.
     *
     * @param  Collection<int, int>  $courseIds
     * @return Collection<int, array{published: int, submitted: int, correct: int, questions: int}>
     */
    public function courseRegister(User $user, Collection $courseIds): Collection
    {
        $courseIds = $courseIds->filter()->values();

        if ($courseIds->isEmpty()) {
            return collect();
        }

        $missions = Mission::query()
            ->whereIn('course_id', $courseIds)
            ->get(['id', 'course_id']);
        $courseByMission = $missions->pluck('course_id', 'id');

        $checks = KnowledgeCheck::query()
            ->whereIn('mission_id', $missions->pluck('id'))
            ->where('status', KnowledgeCheck::STATUS_PUBLISHED)
            ->get(['id', 'mission_id']);

        $published = [];

        foreach ($checks as $check) {
            $courseId = $courseByMission->get($check->mission_id);

            if ($courseId !== null) {
                $published[(int) $courseId] = ($published[(int) $courseId] ?? 0) + 1;
            }
        }

        $submitted = [];
        $correct = [];
        $questions = [];

        if ($checks->isNotEmpty()) {
            $attempts = KnowledgeCheckAttempt::query()
                ->where('user_id', $user->id)
                ->whereIn('knowledge_check_id', $checks->pluck('id'))
                ->where('status', KnowledgeCheckAttempt::STATUS_SUBMITTED)
                ->orderByDesc('attempt_number')
                ->orderByDesc('id')
                ->get()
                ->unique('knowledge_check_id');

            $missionByCheck = $checks->pluck('mission_id', 'id');

            foreach ($attempts as $attempt) {
                $missionId = $missionByCheck->get($attempt->knowledge_check_id);
                $courseId = $missionId !== null ? $courseByMission->get($missionId) : null;

                if ($courseId === null) {
                    continue;
                }

                $courseId = (int) $courseId;

                $submitted[$courseId] = ($submitted[$courseId] ?? 0) + 1;
                $correct[$courseId] = ($correct[$courseId] ?? 0) + (int) $attempt->score;
                $questions[$courseId] = ($questions[$courseId] ?? 0) + (int) $attempt->total_questions;
            }
        }

        return $courseIds->mapWithKeys(fn (int $courseId): array => [
            (int) $courseId => [
                'published' => $published[(int) $courseId] ?? 0,
                'submitted' => $submitted[(int) $courseId] ?? 0,
                'correct' => $correct[(int) $courseId] ?? 0,
                'questions' => $questions[(int) $courseId] ?? 0,
            ],
        ]);
    }

    public function firstOutstandingRequired(User $user, Mission $mission): ?KnowledgeCheck
    {
        return $mission->knowledgeChecks()
            ->where('status', KnowledgeCheck::STATUS_PUBLISHED)
            ->where('is_required', true)
            ->whereDoesntHave('attempts', function (Builder $query) use ($user): void {
                $query
                    ->where('user_id', $user->id)
                    ->where('status', KnowledgeCheckAttempt::STATUS_SUBMITTED);
            })
            ->first();
    }

    /**
     * Start is idempotent. Refreshing or double-clicking the Lesson action
     * returns the existing latest attempt instead of creating history noise.
     */
    public function start(User $user, Mission $mission, KnowledgeCheck $check): KnowledgeCheckAttempt
    {
        $this->assertAvailable($mission, $check);

        return DB::transaction(function () use ($user, $check): KnowledgeCheckAttempt {
            $latest = KnowledgeCheckAttempt::query()
                ->where('knowledge_check_id', $check->id)
                ->where('user_id', $user->id)
                ->orderByDesc('attempt_number')
                ->lockForUpdate()
                ->first();

            if ($latest !== null) {
                return $latest;
            }

            return $this->createAttempt($user, $check, 1);
        }, attempts: 3);
    }

    /**
     * A retry always preserves the submitted row and opens the next numbered
     * attempt. Repeated retry requests while that attempt is open resume it.
     */
    public function retry(User $user, Mission $mission, KnowledgeCheck $check): KnowledgeCheckAttempt
    {
        $this->assertAvailable($mission, $check);

        return DB::transaction(function () use ($user, $check): KnowledgeCheckAttempt {
            $latest = KnowledgeCheckAttempt::query()
                ->where('knowledge_check_id', $check->id)
                ->where('user_id', $user->id)
                ->orderByDesc('attempt_number')
                ->lockForUpdate()
                ->first();

            if ($latest === null) {
                throw KnowledgeCheckStateException::cannotRetry($check->id);
            }

            if ($latest->status === KnowledgeCheckAttempt::STATUS_STARTED) {
                return $latest;
            }

            return $this->createAttempt($user, $check, $latest->attempt_number + 1);
        }, attempts: 3);
    }

    /**
     * Score one complete answer set against the protected option flags.
     * Reposting an already-submitted attempt returns the stored result.
     *
     * @param  array<int|string, int|string>  $answers
     */
    public function submit(
        User $user,
        Mission $mission,
        KnowledgeCheck $check,
        KnowledgeCheckAttempt $attempt,
        array $answers,
    ): KnowledgeCheckAttempt {
        $this->assertAvailable($mission, $check);

        $submitted = DB::transaction(function () use ($user, $check, $attempt, $answers): KnowledgeCheckAttempt {
            $lockedAttempt = KnowledgeCheckAttempt::query()
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            $this->assertAccess($user, $check, $lockedAttempt);

            if ($lockedAttempt->status === KnowledgeCheckAttempt::STATUS_SUBMITTED) {
                return $lockedAttempt;
            }

            $definition = KnowledgeCheck::query()
                ->with(['questions.options', 'questions.skills'])
                ->lockForUpdate()
                ->findOrFail($check->id);

            $normalizedAnswers = $this->normalizeAnswers($answers);
            $this->validateCompleteAnswerSet($definition, $normalizedAnswers);

            $score = 0;

            foreach ($definition->questions as $question) {
                $selected = $question->options->first(
                    fn (KnowledgeCheckOption $option): bool => $option->id === $normalizedAnswers[$question->id],
                );
                $correctOptions = $question->options->filter(
                    fn (KnowledgeCheckOption $option): bool => $option->is_correct,
                );

                if ($question->options->count() < 2 || $selected === null || $correctOptions->count() !== 1) {
                    throw KnowledgeCheckUnavailableException::invalidConfiguration($definition->id);
                }

                /** @var KnowledgeCheckOption $correct */
                $correct = $correctOptions->first();
                $isCorrect = $selected->id === $correct->id;

                if ($isCorrect) {
                    $score++;
                }

                KnowledgeCheckResponse::query()->create([
                    'knowledge_check_attempt_id' => $lockedAttempt->id,
                    'knowledge_check_question_id' => $question->id,
                    'selected_option_id' => $selected->id,
                    'correct_option_id' => $correct->id,
                    'is_correct' => $isCorrect,
                    'prompt_snapshot' => $question->prompt,
                    'selected_option_snapshot' => $selected->option_text,
                    'correct_option_snapshot' => $correct->option_text,
                    'explanation_snapshot' => $question->explanation,
                    // Skill keys evaluated with the answer: later remapping
                    // never reinterpret this row (US-905/US-906).
                    'skill_keys' => $question->skills->pluck('key')->all(),
                ]);
            }

            $total = $definition->questions->count();
            $percentage = max(0, min(100, (int) round(($score / $total) * 100)));

            $lockedAttempt->score = $score;
            $lockedAttempt->total_questions = $total;
            $lockedAttempt->percentage = $percentage;
            // The version stamp must identify the definition actually scored
            // here ($definition, reloaded live in this transaction), not the
            // revision open when the attempt was started. A check edited
            // between start and submit is evaluated — and stamped — as the
            // newer revision; response snapshots carry the evaluated content.
            $lockedAttempt->knowledge_check_version = $definition->version;
            $lockedAttempt->status = KnowledgeCheckAttempt::STATUS_SUBMITTED;
            $lockedAttempt->submitted_at = now();
            $lockedAttempt->save();

            $this->activity->record($user, [
                'type' => self::ACTIVITY_TYPE_COMPLETED,
                'mission_id' => $definition->mission_id,
                'message' => "Knowledge Check completed: {$definition->title} ({$score}/{$total}, {$percentage}%)",
            ]);

            return $lockedAttempt;
        }, attempts: 3);

        return $submitted->load('responses');
    }

    /**
     * Student presentation contains option text and ids only until submission.
     * Correctness and explanations come from immutable response snapshots.
     *
     * @return array{
     *     questions: Collection<int, array{
     *         id: int,
     *         type: 'multiple_choice'|'code_reading'|'concept_identification',
     *         prompt: string,
     *         codeSnippet: string|null,
     *         options: Collection<int, array{id: int, text: string}>,
     *     }>,
     *     responses: Collection<int, array{
     *         questionId: int<0, max>,
     *         prompt: string,
     *         selected: string,
     *         correct: string,
     *         isCorrect: bool,
     *         explanation: string|null,
     *     }>,
     *     nextCheck: KnowledgeCheck|null,
     * }
     */
    public function presentation(
        User $user,
        Mission $mission,
        KnowledgeCheck $check,
        KnowledgeCheckAttempt $attempt,
    ): array {
        $this->assertAvailable($mission, $check);
        $this->assertAccess($user, $check, $attempt);

        $check->load(['questions.options']);
        $attempt->load('responses');

        $questions = $check->questions->map(fn (KnowledgeCheckQuestion $question): array => [
            'id' => $question->id,
            'type' => $question->type,
            'prompt' => $question->prompt,
            'codeSnippet' => $question->code_snippet,
            'options' => $question->options->map(fn (KnowledgeCheckOption $option): array => [
                'id' => $option->id,
                'text' => $option->option_text,
            ]),
        ]);

        $responses = $attempt->status === KnowledgeCheckAttempt::STATUS_SUBMITTED
            ? $attempt->responses->map(fn (KnowledgeCheckResponse $response): array => [
                'questionId' => $response->knowledge_check_question_id,
                'prompt' => $response->prompt_snapshot,
                'selected' => $response->selected_option_snapshot,
                'correct' => $response->correct_option_snapshot,
                'isCorrect' => $response->is_correct,
                'explanation' => $response->explanation_snapshot,
            ])
            : collect();

        return [
            'questions' => $questions,
            'responses' => $responses,
            'nextCheck' => $this->nextPublishedCheck($mission, $check),
        ];
    }

    private function createAttempt(User $user, KnowledgeCheck $check, int $attemptNumber): KnowledgeCheckAttempt
    {
        $attempt = new KnowledgeCheckAttempt([
            'knowledge_check_id' => $check->id,
            'knowledge_check_version' => $check->version,
            'user_id' => $user->id,
            'attempt_number' => $attemptNumber,
            'started_at' => now(),
        ]);
        $attempt->status = KnowledgeCheckAttempt::STATUS_STARTED;
        $attempt->save();

        return $attempt;
    }

    private function assertAvailable(Mission $mission, KnowledgeCheck $check): void
    {
        if ($check->mission_id !== $mission->id || $check->status !== KnowledgeCheck::STATUS_PUBLISHED) {
            throw KnowledgeCheckUnavailableException::forDefinition($check->id);
        }
    }

    private function assertAccess(
        User $user,
        KnowledgeCheck $check,
        KnowledgeCheckAttempt $attempt,
    ): void {
        if ($attempt->user_id !== $user->id || $attempt->knowledge_check_id !== $check->id) {
            throw KnowledgeCheckAccessDeniedException::forAttempt($attempt->id);
        }
    }

    /**
     * @param  array<int|string, int|string>  $answers
     * @return array<int, int>
     */
    private function normalizeAnswers(array $answers): array
    {
        $normalized = [];

        foreach ($answers as $questionId => $optionId) {
            if (! is_numeric($questionId) || ! is_numeric($optionId)) {
                throw ValidationException::withMessages([
                    'answers' => 'Submit one valid option for every question.',
                ]);
            }

            $normalized[(int) $questionId] = (int) $optionId;
        }

        return $normalized;
    }

    /**
     * @param  array<int, int>  $answers
     */
    private function validateCompleteAnswerSet(KnowledgeCheck $check, array $answers): void
    {
        $questionIds = $check->questions->pluck('id')->sort()->values()->all();
        $answerIds = collect(array_keys($answers))->sort()->values()->all();

        if ($questionIds === [] || $questionIds !== $answerIds) {
            throw ValidationException::withMessages([
                'answers' => 'Answer every question before submitting this Knowledge Check.',
            ]);
        }

        foreach ($check->questions as $question) {
            if (! $question->options->contains('id', $answers[$question->id])) {
                throw ValidationException::withMessages([
                    'answers' => 'One or more selected answers do not belong to this Knowledge Check.',
                ]);
            }
        }
    }

    private function nextPublishedCheck(Mission $mission, KnowledgeCheck $check): ?KnowledgeCheck
    {
        return $mission->knowledgeChecks()
            ->where('status', KnowledgeCheck::STATUS_PUBLISHED)
            ->where('order_num', '>', $check->order_num)
            ->first();
    }
}
