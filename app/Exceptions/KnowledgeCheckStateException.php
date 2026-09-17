<?php

namespace App\Exceptions;

use Exception;

class KnowledgeCheckStateException extends Exception
{
    public static function cannotRetry(int $knowledgeCheckId): self
    {
        return new self("Knowledge Check {$knowledgeCheckId} has no submitted attempt to retry.");
    }
}
