<?php

namespace App\Support;

use App\Models\AssessmentAttempt;

/**
 * Explicit whitelist of AssessmentAttempt fields used by student views.
 *
 * Verdict feedback is authored per grading rule / hidden-test summary. It
 * never includes the stored grading_rule, the test configuration, or any
 * other student's data.
 */
final readonly class AssessmentAttemptView
{
    /**
     * @param  list<string>  $failureFeedback
     */
    public function __construct(
        public string $status,
        public ?int $score,
        public ?string $code,
        public ?int $testsTotal = null,
        public ?int $testsPassed = null,
        public array $failureFeedback = [],
    ) {}

    public static function from(AssessmentAttempt $attempt): self
    {
        $feedback = json_decode((string) $attempt->failure_feedback, true);

        return new self(
            status: (string) $attempt->status,
            score: $attempt->score,
            code: $attempt->code,
            testsTotal: $attempt->tests_total,
            testsPassed: $attempt->tests_passed,
            failureFeedback: is_array($feedback)
                ? array_values(array_filter($feedback, 'is_string'))
                : [],
        );
    }
}
