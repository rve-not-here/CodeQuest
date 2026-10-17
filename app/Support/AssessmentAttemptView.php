<?php

namespace App\Support;

use App\Models\AssessmentAttempt;

/**
 * Explicit whitelist of AssessmentAttempt fields used by student views.
 */
final readonly class AssessmentAttemptView
{
    public function __construct(
        public string $status,
        public ?int $score,
        public ?string $code,
    ) {}

    public static function from(AssessmentAttempt $attempt): self
    {
        return new self(
            status: (string) $attempt->status,
            score: $attempt->score,
            code: $attempt->code,
        );
    }
}
