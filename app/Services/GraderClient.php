<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP transport to the internal CodeQuest JS grader service. Sends student
 * source plus server-loaded hidden tests, validates the strict result
 * contract, and fails closed on every transport or contract problem.
 *
 * The response never carries hidden inputs or expected values: the runner
 * reports counts only, and this client drops everything else.
 */
class GraderClient
{
    public const ERROR_TIMEOUT = 'timeout';

    public const ERROR_GRADER_UNAVAILABLE = 'grader_unavailable';

    public const ERROR_INVALID_RESPONSE = 'invalid_response';

    /**
     * @param  list<array{type: string, payload: array<string, mixed>}>  $tests
     * @return array{status: string, tests_total: int, tests_passed: int, duration_ms: int, error_type: string|null}
     */
    public function grade(string $source, array $tests): array
    {
        $url = (string) config('grader.url');
        $timeoutMs = max(1000, (int) config('grader.timeout_ms', 15000));

        if ($url === '') {
            $this->observe(null, null, 'unconfigured', 0, self::ERROR_GRADER_UNAVAILABLE);

            return $this->errorResult(self::ERROR_GRADER_UNAVAILABLE);
        }

        $started = (int) (microtime(true) * 1000);

        try {
            $response = Http::withToken((string) config('grader.token'))
                ->timeout($timeoutMs / 1000)
                ->connectTimeout(5)
                ->acceptJson()
                ->post(rtrim($url, '/').'/grade', [
                    'source' => $source,
                    'tests' => $tests,
                ]);
        } catch (ConnectionException $exception) {
            $this->observe(null, null, 'transport', $this->elapsed($started), self::ERROR_GRADER_UNAVAILABLE);

            return $this->errorResult(self::ERROR_GRADER_UNAVAILABLE);
        }

        if (! $response->successful()) {
            $this->observe(null, null, 'http_'.$response->status(), $this->elapsed($started), self::ERROR_GRADER_UNAVAILABLE);

            return $this->errorResult(self::ERROR_GRADER_UNAVAILABLE);
        }

        $validated = $this->validateContract($response->json(), count($tests));

        if ($validated === null) {
            $this->observe(null, null, 'malformed', $this->elapsed($started), self::ERROR_INVALID_RESPONSE);

            return $this->errorResult(self::ERROR_INVALID_RESPONSE);
        }

        return $validated;
    }

    /**
     * Documented runner error categories. Anything else is not a result
     * CodeQuest understands.
     *
     * @var list<string>
     */
    private const ERROR_TYPES = [
        'syntax',
        'assertion',
        'timeout',
        'resource_limit',
        'runtime',
        'grader_unavailable',
        'invalid_response',
    ];

    /**
     * Strict contract check. Fields must agree semantically, not just parse:
     * a passed result with zero tests, or a failed result with zero tests or
     * all tests passing, is malformed infrastructure data and fails closed
     * instead of becoming an academic failure with a wrong-submission deduction.
     *
     * @return array{status: string, tests_total: int, tests_passed: int, duration_ms: int, error_type: string|null}|null
     */
    private function validateContract(mixed $payload, int $expectedTests): ?array
    {
        if (! is_array($payload) || ($payload['protocol_version'] ?? null) !== 2) {
            return null;
        }

        if (! isset($payload['status'], $payload['tests_total'], $payload['tests_passed'], $payload['duration_ms'])
            || ! in_array($payload['status'], ['passed', 'failed', 'error'], true)
            || ! is_int($payload['tests_total'])
            || ! is_int($payload['tests_passed'])
            || ! is_int($payload['duration_ms'])
            || $payload['duration_ms'] < 0
            || $payload['tests_total'] < 0
            || $payload['tests_passed'] < 0
            || $payload['tests_passed'] > $payload['tests_total']
            || (array_key_exists('error_type', $payload) && ! is_string($payload['error_type']) && $payload['error_type'] !== null)
        ) {
            return null;
        }

        if ($payload['status'] !== 'error' && $payload['tests_total'] !== $expectedTests) {
            return null;
        }

        $errorType = $payload['error_type'] ?? null;

        if ($payload['status'] === 'passed'
            && ($payload['tests_total'] === 0 || $payload['tests_passed'] !== $payload['tests_total'] || $errorType !== null)
        ) {
            return null;
        }

        if ($payload['status'] === 'failed'
            && ($payload['tests_total'] === 0 || $payload['tests_passed'] === $payload['tests_total'])
        ) {
            return null;
        }

        if ($errorType !== null && ! in_array($errorType, self::ERROR_TYPES, true)) {
            return null;
        }

        return [
            'status' => $payload['status'],
            'tests_total' => $payload['tests_total'],
            'tests_passed' => $payload['tests_passed'],
            'duration_ms' => $payload['duration_ms'],
            'error_type' => $errorType,
        ];
    }

    /** @return array{status: string, tests_total: int, tests_passed: int, duration_ms: int, error_type: string|null} */
    private function errorResult(string $errorType): array
    {
        return [
            'status' => 'error',
            'tests_total' => 0,
            'tests_passed' => 0,
            'duration_ms' => 0,
            'error_type' => $errorType,
        ];
    }

    private function elapsed(int $startedMs): int
    {
        return max(0, (int) (microtime(true) * 1000) - $startedMs);
    }

    private function observe(?int $missionId, ?int $userId, string $category, int $durationMs, ?string $errorType): void
    {
        Log::warning('Grader transport issue.', [
            'missionId' => $missionId,
            'userId' => $userId,
            'category' => $category,
            'durationMs' => $durationMs,
            'errorType' => $errorType,
        ]);
    }
}
