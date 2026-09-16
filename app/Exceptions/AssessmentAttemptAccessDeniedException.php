<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a user attempts to read an assessment attempt they do not own.
 * The ownership check is reasoned explicitly in the service, so a student's
 * code and score are never exposed merely by a view omitting other users'
 * records.
 */
class AssessmentAttemptAccessDeniedException extends RuntimeException
{
    public static function forAttempt(int $attemptId): self
    {
        return new self("User is not permitted to view assessment attempt {$attemptId}.");
    }
}
