<?php

namespace App\Exceptions;

use App\Models\KnowledgeCheck;
use App\Models\Mission;
use RuntimeException;

/**
 * Raised when a protected mission mutation is attempted while a published,
 * required Knowledge Check is still outstanding.
 *
 * The rule belongs to the domain, not to a controller: submit, draft, hint
 * and solution reveal all reach it through KnowledgeCheckGate, so a future
 * caller cannot bypass the prerequisite by using the service directly.
 */
class KnowledgeCheckRequiredException extends RuntimeException
{
    public function __construct(
        public readonly Mission $mission,
        public readonly KnowledgeCheck $check,
    ) {
        parent::__construct("Complete {$check->title} before continuing with {$mission->title}.");
    }

    public static function for(Mission $mission, KnowledgeCheck $check): self
    {
        return new self($mission, $check);
    }
}
