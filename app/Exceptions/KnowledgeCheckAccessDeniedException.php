<?php

namespace App\Exceptions;

use Exception;

class KnowledgeCheckAccessDeniedException extends Exception
{
    public static function forAttempt(int $attemptId): self
    {
        return new self("Knowledge Check attempt {$attemptId} is not available to this user.");
    }
}
