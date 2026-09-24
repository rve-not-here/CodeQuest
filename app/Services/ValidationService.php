<?php

namespace App\Services;

use App\Models\Mission;
use InvalidArgumentException;
use RuntimeException;

/**
 * Evaluates a submitted solution against a mission's validate_rule JSON.
 *
 * Rules are pure string/structural checks. The class never runs submitted
 * code and never invokes a code-execution builtin, and there is no server-side
 * JS interpreter. Submissions are matched against declared patterns only. A
 * mission without validate_rule passes trivially (nothing to check); this is a
 * seed-data concern, not a bypass.
 */
class ValidationService
{
    /**
     * Validate a submission against a mission's rules.
     *
     * @return array{passed: bool, failures: list<string>, total: int}
     */
    public function validate(Mission $mission, string $code): array
    {
        return $this->validateRules($mission->validate_rule ?? '', $code);
    }

    /**
     * Validate a submission against a raw JSON rules string.
     *
     * Shared by missions (via validate()) and assessments (the grading_rule
     * column), so the pattern engine is not duplicated. Rules are pure
     * string/structural checks. The result includes the total rule count so a
     * caller can derive a score as the proportion of rules passed.
     *
     * @return array{passed: bool, failures: list<string>, total: int}
     */
    public function validateRules(string $rulesJson, string $code): array
    {
        if (trim($rulesJson) === '') {
            return ['passed' => true, 'failures' => [], 'total' => 0];
        }

        $rules = json_decode($rulesJson, true);

        if (! is_array($rules)) {
            return ['passed' => true, 'failures' => [], 'total' => 0];
        }

        $failures = [];

        foreach ($rules as $index => $rule) {
            try {
                $passed = $this->evaluate($rule, $code);
            } catch (InvalidArgumentException) {
                $failures[] = 'Challenge validation is unavailable. Try again later.';

                continue;
            }

            $label = $this->ruleLabel($rule, $index);

            if ($rule['negate'] ?? false) {
                $passed = ! $passed;
            }

            if (! $passed) {
                $failures[] = $this->failureMessage($label, $rule);
            }
        }

        return [
            'passed' => count($failures) === 0,
            'failures' => $failures,
            'total' => count($rules),
        ];
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function evaluate(array $rule, string $code): bool
    {
        $type = $rule['type'] ?? null;

        return match ($type) {
            'contains' => $this->contains($code, $rule),
            'contains_all' => $this->containsAll($code, $rule),
            'contains_any' => $this->containsAny($code, $rule),
            'count_tag' => $this->countTag($code, $rule),
            'count' => $this->count($code, $rule),
            'regex' => $this->regex($code, $rule),
            'exact_normalized' => $this->exactNormalized($code, $rule),
            default => throw new InvalidArgumentException("Unknown rule type '{$type}'"),
        };
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function contains(string $code, array $rule): bool
    {
        $needle = $this->needString($rule);

        return str_contains($code, $needle);
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function containsAll(string $code, array $rule): bool
    {
        $needles = $this->needStringArray($rule, 'values');

        foreach ($needles as $needle) {
            if (! str_contains($code, $needle)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function containsAny(string $code, array $rule): bool
    {
        $needles = $this->needStringArray($rule, 'values');

        foreach ($needles as $needle) {
            if (str_contains($code, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function countTag(string $code, array $rule): bool
    {
        $tag = $this->needString($rule, 'tag');
        $expected = $this->needInt($rule, 'count');

        $occurrences = @preg_match_all('/<'.$tag.'[\s>]/i', $code);

        if ($occurrences === false) {
            throw new RuntimeException('Invalid count_tag pattern.');
        }

        return $this->compareCount($occurrences, $expected, $rule);
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function count(string $code, array $rule): bool
    {
        $needle = $this->needString($rule, 'value');
        $expected = $this->needInt($rule, 'count');

        $occurrences = substr_count($code, $needle);

        return $this->compareCount($occurrences, $expected, $rule);
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function regex(string $code, array $rule): bool
    {
        $pattern = $this->needString($rule, 'pattern');

        $pattern = '/'.$pattern.'/';

        if (@preg_match($pattern, $code) === 1) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function exactNormalized(string $code, array $rule): bool
    {
        $expected = $this->needString($rule, 'value');

        return $this->normalize($code) === $this->normalize($expected);
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function compareCount(int $occurrences, int $expected, array $rule): bool
    {
        return match ($rule['operator'] ?? 'eq') {
            'eq' => $occurrences === $expected,
            'gte' => $occurrences >= $expected,
            'lte' => $occurrences <= $expected,
            'gt' => $occurrences > $expected,
            'lt' => $occurrences < $expected,
            default => throw new InvalidArgumentException("Unknown count operator '".($rule['operator'] ?? '')."'"),
        };
    }

    private function normalize(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', '', $value) ?? $value));
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function needString(array $rule, string $key = 'value'): string
    {
        $value = $rule[$key] ?? null;

        if (! is_string($value)) {
            throw new InvalidArgumentException("Rule is missing a string '{$key}'.");
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $rule
     * @return array<int, string>
     */
    private function needStringArray(array $rule, string $key): array
    {
        $value = $rule[$key] ?? null;

        if (! is_array($value)) {
            throw new InvalidArgumentException("Rule is missing an array '{$key}'.");
        }

        return array_values(array_map('strval', $value));
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function needInt(array $rule, string $key = 'count'): int
    {
        $value = $rule[$key] ?? null;

        if (! is_int($value)) {
            throw new InvalidArgumentException("Rule is missing an integer '{$key}'.");
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function ruleLabel(array $rule, int $index): string
    {
        if (isset($rule['label']) && is_string($rule['label']) && $rule['label'] !== '') {
            return $rule['label'];
        }

        return 'Requirement '.($index + 1);
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function failureMessage(string $label, array $rule): string
    {
        $negate = $rule['negate'] ?? false;
        $message = $rule['message'] ?? null;

        if (is_string($message) && $message !== '') {
            return $message;
        }

        if ($negate) {
            return $label.' must not match.';
        }

        return $label.' check failed.';
    }
}
