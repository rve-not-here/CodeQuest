<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\MissionBehaviorTest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Structural plus hidden behavioral grading for mission submissions.
 *
 * Legacy missions (no active behavior tests) keep pure structural
 * validation and never touch the grader. Missions carrying tests require
 * structural PASS and behavioral PASS together. Anything the grader cannot
 * decide (unconfigured, too large, malformed, transport failure) fails
 * closed: no completion, no XP, and no wrong-submission deduction, since an
 * infrastructure problem is not a student failure.
 */
class MissionGradingService
{
    public function __construct(
        private readonly ValidationService $validator,
        private readonly GraderClient $grader,
    ) {}

    /**
     * @return array{passed: bool, failures: list<string>, unavailable: bool, tests_total: int, tests_passed: int}
     */
    public function grade(User $user, Mission $mission, string $code): array
    {
        $tests = $mission->behaviorTests()->get();

        if (! $this->validator->isValidDefinition($mission->validate_rule ?? '', allowEmpty: $tests->isNotEmpty())) {
            $this->observe($user, $mission, 'malformed-rules', 0, 'invalid_response');

            return $this->unavailable();
        }

        $maxSource = max(1024, (int) config('grader.max_source_bytes', 65536));
        $maxTests = max(1, (int) config('grader.max_tests_per_mission', 20));

        if ($tests->isNotEmpty() && (strlen($code) > $maxSource || $tests->count() > $maxTests)) {
            $this->observe($user, $mission, 'bounds', 0, 'invalid_response');

            return $this->unavailable();
        }

        $payload = [];

        foreach ($tests as $test) {
            $shaped = $this->shapeTest($test->test_type, $test->decodedConfiguration());

            if ($shaped === null) {
                $this->observe($user, $mission, 'malformed-test', 0, 'invalid_response');

                return $this->unavailable();
            }

            $payload[] = $shaped;
        }

        try {
            $structural = $this->validator->validate($mission, $code);
        } catch (RuntimeException) {
            $this->observe($user, $mission, 'rule-evaluation-unavailable', 0, 'invalid_response');

            return $this->unavailable();
        }

        if (! $structural['passed']) {
            return [
                'passed' => false,
                'failures' => $structural['failures'],
                'unavailable' => false,
                'tests_total' => 0,
                'tests_passed' => 0,
            ];
        }

        if ($tests->isEmpty()) {
            return [
                'passed' => true,
                'failures' => [],
                'unavailable' => false,
                'tests_total' => 0,
                'tests_passed' => 0,
            ];
        }

        $result = $this->grader->grade($code, $payload);

        $this->observe(
            $user,
            $mission,
            $result['status'],
            $result['duration_ms'],
            $result['error_type']
        );

        if ($result['status'] === 'passed' && $result['tests_total'] > 0
            && $result['tests_passed'] === $result['tests_total']) {
            return [
                'passed' => true,
                'failures' => [],
                'unavailable' => false,
                'tests_total' => $result['tests_total'],
                'tests_passed' => $result['tests_passed'],
            ];
        }

        if ($result['status'] === 'failed') {
            return [
                'passed' => false,
                'failures' => [
                    "Behavioral checks failed: {$result['tests_passed']} of {$result['tests_total']} hidden tests passed. Your program returned a wrong result for a hidden case.",
                ],
                'unavailable' => false,
                'tests_total' => $result['tests_total'],
                'tests_passed' => $result['tests_passed'],
            ];
        }

        return $this->unavailable($result['tests_total'], $result['tests_passed']);
    }

    /**
     * Keep only well-shaped tests. Anything else is a content error, and
     * content errors fail closed rather than passing blindly.
     *
     * @param  array<string|int, mixed>  $configuration
     * @return array{type: string, payload: array<string, mixed>}|null
     */
    private function shapeTest(string $type, array $configuration): ?array
    {
        if ($type === MissionBehaviorTest::TYPE_FUNCTION) {
            if (! isset($configuration['function']) || ! is_string($configuration['function'])
                || $configuration['function'] === '' || strlen($configuration['function']) > 120
                || ! isset($configuration['cases']) || ! is_array($configuration['cases'])
                || $configuration['cases'] === [] || count($configuration['cases']) > 50
            ) {
                return null;
            }

            foreach ($configuration['cases'] as $case) {
                if (! is_array($case) || ! array_key_exists('expected', $case)
                    || ! isset($case['args']) || ! is_array($case['args']) || count($case['args']) > 10
                    || ! $this->jsonSafe($case['args']) || ! $this->jsonSafe($case['expected'])
                ) {
                    return null;
                }
            }

            return ['type' => $type, 'payload' => $this->stringKeys($configuration)];
        }

        if ($type === MissionBehaviorTest::TYPE_CONSOLE) {
            if (! isset($configuration['expected']) || ! is_array($configuration['expected'])
                || $configuration['expected'] === [] || count($configuration['expected']) > 50
            ) {
                return null;
            }

            foreach ($configuration['expected'] as $entry) {
                if (! is_array($entry) || count($entry) > 10 || ! $this->jsonSafe($entry)) {
                    return null;
                }
            }

            return ['type' => $type, 'payload' => $this->stringKeys($configuration)];
        }

        return null;
    }

    /**
     * JSON objects decode with string keys. Keep exactly those so the
     * payload type stays honest for the grader transport.
     *
     * @param  array<string|int, mixed>  $configuration
     * @return array<string, mixed>
     */
    private function stringKeys(array $configuration): array
    {
        $payload = [];

        foreach ($configuration as $key => $value) {
            if (is_string($key)) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    private function jsonSafe(mixed $value): bool
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return is_string($value) ? strlen($value) <= 4096 : true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (! $this->jsonSafe($item)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /** @return array{passed: bool, failures: list<string>, unavailable: bool, tests_total: int, tests_passed: int} */
    private function unavailable(int $total = 0, int $passed = 0): array
    {
        return [
            'passed' => false,
            'failures' => ['Challenge grading is unavailable right now. Your code was not graded and no XP changed. Try again later.'],
            'unavailable' => true,
            'tests_total' => $total,
            'tests_passed' => $passed,
        ];
    }

    private function observe(User $user, Mission $mission, string $category, int $durationMs, ?string $errorType): void
    {
        Log::info('Mission behavioral grading decision.', [
            'missionId' => $mission->id,
            'userId' => $user->id,
            'category' => $category,
            'durationMs' => $durationMs,
            'errorType' => $errorType,
        ]);
    }
}
