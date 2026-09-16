<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an attempt is made to create a second assessment for a course
 * that already has one. Each course has exactly one Boss Challenge (§3.2).
 * Thrown at the application layer so callers get a clear domain error instead
 * of the raw database unique-constraint violation surfacing.
 */
class AssessmentAlreadyExistsException extends RuntimeException
{
    public static function forCourse(int $courseId): self
    {
        return new self("Course {$courseId} already has an assessment.");
    }
}
