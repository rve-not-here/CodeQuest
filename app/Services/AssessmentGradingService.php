<?php

namespace App\Services;

use App\Models\Assessment;
use App\Support\BehaviorTestDefinition;
use App\Support\SchemaAvailability;
use Illuminate\Support\Facades\Log;

/**
 * Hidden behavioural grading for a Boss Challenge.
 *
 * The summative assessment keeps its authored pattern rules as a structural
 * gate, and adds server-loaded hidden tests executed by the sandboxed
 * grader. A semantically wrong submission that matches the visible patterns
 * therefore still fails. With no active tests the assessment keeps the
 * historical pattern-only evaluation, and callers can tell the difference
 * through `configured`.
 *
 * Every content or transport problem returns `available: false`. The caller
 * must treat that as an infrastructure failure (attempt stays ungraded),
 * never as a student failure — the same contract MissionGradingService uses.
 */
class AssessmentGradingService
{
    public function __construct(
        private readonly GraderClient $grader,
    ) {}

    /**
     * @return array{
     *     configured: bool,
     *     available: bool,
     *     passed: bool,
     *     tests_total: int,
     *     tests_passed: int,
     *     failures: list<string>,
     * }
     */
    public function grade(Assessment $assessment, string $code): array
    {
        if (! SchemaAvailability::assessmentBehaviorTests()) {
            // No hidden-test storage means no hidden test can exist for this
            // assessment, so the legacy pattern-only evaluation is the honest
            // one for this schema state. The pending migration is logged by
            // SchemaAvailability so the gap stays visible.
            return $this->result(configured: false, available: true, passed: true);
        }

        $tests = $assessment->behaviorTests()
            ->where('active', true)
            ->orderBy('order_num')
            ->get();

        if ($tests->isEmpty()) {
            return $this->result(configured: false, available: true, passed: true);
        }

        $maxSource = max(1024, (int) config('grader.max_source_bytes', 65536));
        $maxTests = max(1, (int) config('grader.max_tests_per_mission', 20));

        if (strlen($code) > $maxSource || $tests->count() > $maxTests) {
            return $this->unavailable();
        }

        $payload = [];

        foreach ($tests as $test) {
            $shaped = BehaviorTestDefinition::shape($test->test_type, $test->decodedConfiguration());

            if ($shaped === null) {
                return $this->unavailable();
            }

            $payload[] = $shaped;
        }

        $result = $this->grader->grade($code, $payload);

        if ($result['status'] === 'passed'
            && $result['tests_total'] > 0
            && $result['tests_passed'] === $result['tests_total']) {
            return $this->result(
                configured: true,
                available: true,
                passed: true,
                testsTotal: $result['tests_total'],
                testsPassed: $result['tests_passed'],
            );
        }

        if ($result['status'] === 'failed') {
            return $this->result(
                configured: true,
                available: true,
                passed: false,
                testsTotal: $result['tests_total'],
                testsPassed: $result['tests_passed'],
                failures: [
                    "Behavioural checks failed: {$result['tests_passed']} of {$result['tests_total']} hidden tests passed. Your program returned a wrong result for a hidden case.",
                ],
            );
        }

        return $this->unavailable($result['tests_total'], $result['tests_passed']);
    }

    /**
     * @param  list<string>  $failures
     * @return array{
     *     configured: bool,
     *     available: bool,
     *     passed: bool,
     *     tests_total: int,
     *     tests_passed: int,
     *     failures: list<string>,
     * }
     */
    private function result(
        bool $configured,
        bool $available,
        bool $passed,
        int $testsTotal = 0,
        int $testsPassed = 0,
        array $failures = [],
    ): array {
        return [
            'configured' => $configured,
            'available' => $available,
            'passed' => $passed,
            'tests_total' => $testsTotal,
            'tests_passed' => $testsPassed,
            'failures' => $failures,
        ];
    }

    /**
     * @return array{
     *     configured: bool,
     *     available: bool,
     *     passed: bool,
     *     tests_total: int,
     *     tests_passed: int,
     *     failures: list<string>,
     * }
     */
    private function unavailable(int $testsTotal = 0, int $testsPassed = 0): array
    {
        Log::warning('Boss Challenge behavioural grading unavailable.', [
            'tests_total' => $testsTotal,
            'tests_passed' => $testsPassed,
        ]);

        return $this->result(
            configured: true,
            available: false,
            passed: false,
            testsTotal: $testsTotal,
            testsPassed: $testsPassed,
            failures: ['Boss Challenge grading is unavailable right now. Your code was not graded and no XP changed. Try again later.'],
        );
    }
}
