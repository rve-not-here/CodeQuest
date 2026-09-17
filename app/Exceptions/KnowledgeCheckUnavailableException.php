<?php

namespace App\Exceptions;

use Exception;

class KnowledgeCheckUnavailableException extends Exception
{
    public static function forDefinition(int $knowledgeCheckId): self
    {
        return new self("Knowledge Check {$knowledgeCheckId} is not available for this lesson.");
    }

    public static function invalidConfiguration(int $knowledgeCheckId): self
    {
        return new self("Knowledge Check {$knowledgeCheckId} is not configured for submission.");
    }
}
