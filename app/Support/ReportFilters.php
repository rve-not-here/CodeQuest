<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Validated report filters (US-1009). The single reusable filtering
 * contract future reports consume instead of parsing raw request input.
 *
 * Only the four spec-required dimensions exist: date range, student,
 * course, status. Validation proves a filter is well-formed; it never
 * proves anyone may see the filtered rows. Authorization stays entirely
 * with ReportAuthorizationService — a valid filter for an unauthorized
 * target still fails closed there.
 *
 * Dates are calendar days in the application timezone (UTC) normalized to
 * a half-open range: from 00:00:00 inclusive up to the day after `to` at
 * 00:00:00 exclusive. Half-open bounds cannot drop rows to timestamp
 * precision the way a 23:59:59 upper bound can.
 */
final class ReportFilters
{
    private function __construct(
        public readonly ?CarbonImmutable $from,
        public readonly ?CarbonImmutable $toExclusive,
        public readonly ?int $studentId,
        public readonly ?int $courseId,
        public readonly ?string $status,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, string>  $allowedStatuses  the calling report's finite status vocabulary
     * @param  list<int>|null  $studentIds  null permits fleet-wide existence validation
     * @param  list<int>|null  $courseIds  an empty list permits no courses
     *
     * @throws ValidationException
     */
    public static function fromArray(array $input, array $allowedStatuses = [], ?array $studentIds = null, ?array $courseIds = null): self
    {
        $from = self::parseDate($input['from'] ?? null, 'from');
        $to = self::parseDate($input['to'] ?? null, 'to');

        if ($from !== null && $to !== null && $from->greaterThan($to)) {
            throw ValidationException::withMessages([
                'from' => 'The from date must be on or before the to date.',
            ]);
        }

        return new self(
            $from?->startOfDay(),
            $to?->addDay()->startOfDay(),
            self::parseStudentId($input['student_id'] ?? null, $studentIds),
            self::parseCourseId($input['course_id'] ?? null, $courseIds),
            self::parseStatus($input['status'] ?? null, $allowedStatuses),
        );
    }

    /**
     * Apply the normalized date range to a timestamp column. Column name
     * comes from calling code, never from request input.
     *
     * @template TBuilder of QueryBuilder|EloquentBuilder<Model>
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function applyDateRange($query, string $column)
    {
        if ($this->from !== null) {
            $query->where($column, '>=', $this->from->toDateTimeString());
        }

        if ($this->toExclusive !== null) {
            $query->where($column, '<', $this->toExclusive->toDateTimeString());
        }

        return $query;
    }

    /**
     * Strict calendar-date parsing: YYYY-MM-DD shape plus a real calendar
     * day. strtotime-style parsing would silently roll '2026-02-30' into
     * March, so it is not used here.
     *
     * @throws ValidationException
     */
    private static function parseDate(mixed $value, string $field): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw ValidationException::withMessages([
                $field => "The {$field} date must use YYYY-MM-DD format.",
            ]);
        }

        [$year, $month, $day] = array_map(intval(...), explode('-', $value));

        if (! checkdate($month, $day, $year)) {
            throw ValidationException::withMessages([
                $field => "The {$field} date is not a real calendar day.",
            ]);
        }

        return CarbonImmutable::create($year, $month, $day, 0, 0, 0, config('app.timezone'));
    }

    /**
     * @param  list<int>|null  $studentIds
     *
     * @throws ValidationException
     */
    private static function parseStudentId(mixed $value, ?array $studentIds): ?int
    {
        $id = self::parseId($value, 'student_id');

        if ($id === null) {
            return null;
        }

        $isStudent = DB::table('the404_users')
            ->where('id', $id)
            ->where('role', 'student')
            ->when($studentIds !== null, fn (QueryBuilder $query): QueryBuilder => $query->whereIn('id', $studentIds))
            ->exists();

        if (! $isStudent) {
            throw ValidationException::withMessages([
                'student_id' => 'The selected student is invalid.',
            ]);
        }

        return $id;
    }

    /**
     * @param  list<int>|null  $courseIds
     *
     * @throws ValidationException
     */
    private static function parseCourseId(mixed $value, ?array $courseIds): ?int
    {
        $id = self::parseId($value, 'course_id');

        if ($id === null) {
            return null;
        }

        $exists = DB::table('the404_courses')->where('id', $id)
            ->when($courseIds !== null, fn (QueryBuilder $query): QueryBuilder => $query->whereIn('id', $courseIds))
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'course_id' => 'The selected course is invalid.',
            ]);
        }

        return $id;
    }

    /**
     * @throws ValidationException
     */
    private static function parseId(mixed $value, string $field): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value) || ! ctype_digit($value)) {
            throw ValidationException::withMessages([
                $field => "The selected {$field} is invalid.",
            ]);
        }

        return (int) $value;
    }

    /**
     * Status is valid only inside the calling report's finite vocabulary.
     * Anything else — including any value when the report declares no
     * vocabulary — fails explicitly instead of being silently ignored.
     *
     * @param  array<int, string>  $allowedStatuses
     *
     * @throws ValidationException
     */
    private static function parseStatus(mixed $value, array $allowedStatuses): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || ! in_array($value, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'The selected status is invalid for this report.',
            ]);
        }

        return $value;
    }
}
