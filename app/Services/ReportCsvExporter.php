<?php

namespace App\Services;

/**
 * CSV serialization for the accepted Phase 10 reports (US-1010). A pure
 * rendering concern: every method takes an already-authorized report array
 * and returns CSV text. No database access, no authorization, no filtering,
 * no formulas — values move verbatim from the report into cells, with only
 * type formatting (bool/null/number) and spreadsheet-injection protection.
 *
 * Safety contract:
 * - fputcsv() writes every row; cells are never concatenated by hand, so
 *   commas, quotes, CR/LF, and Unicode survive round-trips quoted.
 * - a cell whose text starts with =, +, -, or @ is prefixed with a single
 *   quote, so user-controlled titles and labels cannot become spreadsheet
 *   formulas. Null stays an empty field (distinct from numeric zero) and is
 *   never prefixed; booleans render as true/false and cannot trigger it.
 * - output is UTF-8 with a BOM for spreadsheet compatibility.
 * - column order is fixed per dataset; nested report data is never
 *   flattened — each dataset serializes exactly one flat report subsection.
 */
class ReportCsvExporter
{
    /**
     * @param  list<string>  $header
     * @param  iterable<array<int, mixed>>  $rows
     */
    public function table(array $header, iterable $rows): string
    {
        $handle = fopen('php://memory', 'r+b');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open CSV memory stream.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $header);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(self::cell(...), array_values($row)));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    /**
     * Render one cell. Null stays empty (never zero, never prefixed);
     * booleans spell out; numbers stringify; text is injection-guarded.
     */
    public static function cell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $text = (string) $value;

        if ($text !== '' && str_contains('=+-@', $text[0])) {
            return "'".$text;
        }

        return $text;
    }

    /**
     * Per-course progress rows (student and teacher-student reports).
     *
     * @param  list<array<string, mixed>>  $courses
     */
    public function courses(array $courses): string
    {
        return $this->table(
            ['course_id', 'name', 'state', 'percent', 'completed_missions', 'total_missions', 'challenge_passed'],
            array_map(fn (array $row): array => [
                $row['course_id'],
                $row['name'],
                $row['state'],
                $row['percent'],
                $row['completed_missions'],
                $row['total_missions'],
                $row['challenge_passed'],
            ], $courses),
        );
    }

    /**
     * Per-skill competency rows (student and teacher-student reports).
     *
     * @param  list<array<string, mixed>>  $skills
     */
    public function skills(array $skills): string
    {
        return $this->table(
            ['key', 'label', 'percentage', 'state', 'weak', 'kc_correct', 'kc_total', 'challenges_completed', 'challenges_applicable'],
            array_map(fn (array $row): array => [
                $row['key'],
                $row['label'],
                $row['percentage'],
                $row['state'],
                $row['weak'],
                $row['kc_correct'],
                $row['kc_total'],
                $row['challenges_completed'],
                $row['challenges_applicable'],
            ], $skills),
        );
    }

    /**
     * Per-student course-state rows (teacher course report).
     *
     * @param  list<array<string, mixed>>  $students
     */
    public function students(array $students): string
    {
        return $this->table(
            ['student_id', 'state', 'percent'],
            array_map(fn (array $row): array => [
                $row['student_id'],
                $row['state'],
                $row['percent'],
            ], $students),
        );
    }

    /**
     * Per-challenge rows, verbatim from the owning analytics service.
     *
     * @param  list<array<string, mixed>>  $challenges
     */
    public function challenges(array $challenges): string
    {
        return $this->table(
            ['mission_id', 'course_id', 'title', 'difficulty', 'attempts', 'completions', 'failures', 'completion_rate', 'average_attempts', 'failure_rate'],
            array_map(fn (array $row): array => [
                $row['mission_id'],
                $row['course_id'],
                $row['title'],
                $row['difficulty'],
                $row['attempts'],
                $row['completions'],
                $row['failures'],
                $row['completion_rate'],
                $row['average_attempts'],
                $row['failure_rate'],
            ], $challenges),
        );
    }

    /**
     * Per-assessment rows, verbatim from the owning analytics service.
     *
     * @param  list<array<string, mixed>>  $assessments
     */
    public function assessments(array $assessments): string
    {
        return $this->table(
            ['assessment_id', 'course_id', 'title', 'attempts', 'passes', 'failures', 'average_score', 'pass_rate', 'retries', 'completion'],
            array_map(fn (array $row): array => [
                $row['assessment_id'],
                $row['course_id'],
                $row['title'],
                $row['attempts'],
                $row['passes'],
                $row['failures'],
                $row['average_score'],
                $row['pass_rate'],
                $row['retries'],
                $row['completion'],
            ], $assessments),
        );
    }

    /**
     * Single-row aggregate sections as deterministic metric/value pairs.
     *
     * @param  array<string, mixed>  $metrics
     */
    public function metrics(array $metrics): string
    {
        $rows = [];

        foreach ($metrics as $metric => $value) {
            $rows[] = [$metric, $value];
        }

        return $this->table(['metric', 'value'], $rows);
    }
}
