<?php

namespace App\Services;

use App\Exceptions\KnowledgeCheckRequiredException;
use App\Models\KnowledgeCheck;
use App\Models\Mission;
use App\Models\Progress;
use App\Models\User;

/**
 * The domain boundary for the required Knowledge Check prerequisite.
 *
 * A published, required check gates its mission's challenge work until any
 * attempt is authoritatively submitted (the approved gate policy — see
 * .ai/rules/models-services.md: a submitted attempt satisfies the check,
 * pass or fail). The rule is formative: it gates NEW work, so a mission the
 * student has already completed is exempt rather than locked out of review.
 *
 * This service deliberately depends only on KnowledgeCheckService. Injecting
 * AssessmentService here would close a container cycle, because
 * AssessmentService injects XpService, which is one of the guarded operations.
 */
class KnowledgeCheckGate
{
    public function __construct(
        private readonly KnowledgeCheckService $checks,
    ) {}

    /**
     * The check that still blocks work on this mission, if any.
     */
    public function outstanding(User $user, Mission $mission): ?KnowledgeCheck
    {
        if ($this->isCompleted($user, $mission)) {
            return null;
        }

        return $this->checks->firstOutstandingRequired($user, $mission);
    }

    /**
     * Refuse a protected mutation. Throws rather than returning a boolean so
     * no caller can accidentally ignore the prerequisite.
     *
     * @throws KnowledgeCheckRequiredException
     */
    public function assertCleared(User $user, Mission $mission): void
    {
        $check = $this->outstanding($user, $mission);

        if ($check !== null) {
            throw KnowledgeCheckRequiredException::for($mission, $check);
        }
    }

    public function isCompleted(User $user, Mission $mission): bool
    {
        return Progress::query()
            ->where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->exists();
    }
}
