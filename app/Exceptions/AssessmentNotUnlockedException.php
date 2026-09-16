<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a student tries to begin a Boss Challenge they are not unlocked
 * for (§6/US-404). The attempt is refused at the application layer so the
 * gating lives in the service, never the view.
 */
class AssessmentNotUnlockedException extends RuntimeException
{
    public static function forCourse(int $courseId): self
    {
        return new self("Assessment for course {$courseId} is not unlocked for this student.");
    }
}
