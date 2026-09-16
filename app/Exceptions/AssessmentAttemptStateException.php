<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an action is attempted against an assessment attempt whose
 * state does not permit it. One class covers every lifecycle-state mismatch
 * across the attempt verbs (begin, submit, evaluate, retry): it names the
 * state the attempt is actually in and the states the action expects, so the
 * failure reads as a state-machine violation rather than an opaque error.
 */
class AssessmentAttemptStateException extends RuntimeException
{
    /**
     * @param  array<int, string>  $expected
     */
    public static function mismatch(int $attemptId, array $expected, string $actual): self
    {
        return new self(sprintf(
            'Assessment attempt %d is in state "%s", expected %s.',
            $attemptId,
            $actual,
            implode(' or ', $expected)
        ));
    }

    public static function noAttemptToRetry(int $courseId): self
    {
        return new self("Course {$courseId} has no prior assessment attempt to retry; begin the challenge first.");
    }
}
