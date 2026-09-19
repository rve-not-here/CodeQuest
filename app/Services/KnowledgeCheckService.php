<?php

namespace App\Services;

use App\Exceptions\KnowledgeCheckAccessDeniedException;
use App\Exceptions\KnowledgeCheckStateException;
use App\Exceptions\KnowledgeCheckUnavailableException;
use App\Models\KnowledgeCheck;
use App\Models\KnowledgeCheckAttempt;
use App\Models\KnowledgeCheckOption;
use App\Models\KnowledgeCheckQuestion;
use App\Models\KnowledgeCheckResponse;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KnowledgeCheckService
{
    public const ACTIVITY_TYPE_COMPLETED = 'knowledge_check_completed';

    public function __construct(
        private readonly ActivityService $activity,
    ) {}

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
