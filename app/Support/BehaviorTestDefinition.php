<?php

namespace App\Support;

/**
 * The one shaping/validation path for hidden behavioural tests.
 *
 * Missions and Boss Challenges store the same test definition shape, so the
 * grading payload is built by exactly one implementation. A definition that
 * is not well-formed returns null, and every caller treats null as a content
 * error that fails closed — never as a passing test.
 *
 * function configuration shape:
 * {function: string, cases: list<array{args: list<mixed>, expected: mixed}>}
 *
 * console configuration shape:
 * {expected: list<list<mixed>>} (ordered console.log/info argument lists)
 */
class BehaviorTestDefinition
{
    public const TYPE_FUNCTION = 'function';

    public const TYPE_CONSOLE = 'console';

    /**
     * @param  array<string|int, mixed>  $configuration
     * @return array{type: string, payload: array<string, mixed>}|null
     */
    public static function shape(string $type, array $configuration): ?array
    {
        if ($type === self::TYPE_FUNCTION) {
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
                    || ! self::jsonSafe($case['args']) || ! self::jsonSafe($case['expected'])
                ) {
                    return null;
                }
            }

            return ['type' => $type, 'payload' => self::stringKeys($configuration)];
        }

        if ($type === self::TYPE_CONSOLE) {
            if (! isset($configuration['expected']) || ! is_array($configuration['expected'])
                || $configuration['expected'] === [] || count($configuration['expected']) > 50
            ) {
                return null;
            }

            foreach ($configuration['expected'] as $entry) {
                if (! is_array($entry) || count($entry) > 10 || ! self::jsonSafe($entry)) {
                    return null;
                }
            }

            return ['type' => $type, 'payload' => self::stringKeys($configuration)];
        }

        return null;
    }

    /**
     * @param  array<string|int, mixed>  $configuration
     * @return array<string, mixed>
     */
    private static function stringKeys(array $configuration): array
    {
        $payload = [];

        foreach ($configuration as $key => $value) {
            if (is_string($key)) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    private static function jsonSafe(mixed $value): bool
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return is_string($value) ? strlen($value) <= 4096 : true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (! self::jsonSafe($item)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
