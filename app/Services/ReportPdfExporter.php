<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

/**
 * PDF serialization for the accepted Phase 10 reports (US-1010). A pure
 * rendering concern layered exactly like the CSV exporter: every method
 * takes an already-authorized report array and returns PDF bytes. No
 * database access, no authorization, no filtering, no formulas — values move
 * verbatim from the report into a view model, are formatted as display
 * text, and render through one generic Blade template that escapes every
 * cell. Controllers feed it only the arrays their report service returned.
 *
 * Temporal labeling contract: every section carries a note naming its time
 * semantics (cumulative current state, fixed authoritative window,
 * all-time-or-selected-period through the owning service, or selected
 * movement), so a selected date range can never be misread as filtering a
 * section it does not narrow.
 */
class ReportPdfExporter
{
    /**
     * Hardened Dompdf options. Remote resources, embedded PHP, and PDF
     * JavaScript stay off — reports have no legitimate need for executable
     * PDF content — and the remote-host allow-list is empty as defense in
     * depth while remote loading remains disabled. No local assets are
     * referenced (inline styles only), and report data never reaches URLs,
     * paths, imports, images, or renderer options.
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'isJavascriptEnabled' => false,
            'allowedRemoteHosts' => [],
        ];
    }

    /**
     * @param  list<array{label: string, value: string}>  $meta
     * @param  list<array{heading: string, note: string|null, columns: list<string>, rows: list<list<string>>}>  $sections
     * @return array{title: string, meta: list<array{label: string, value: string}>, sections: list<array{heading: string, note: string|null, columns: list<string>, rows: list<list<string>>}>}
     */
    public function document(string $title, array $meta, array $sections): array
    {
        return [
            'title' => $title,
            'meta' => $meta,
            'sections' => $sections,
        ];
    }

    /**
     * @param  list<string>  $columns
     * @param  list<list<string>>  $rows
     * @return array{heading: string, note: string|null, columns: list<string>, rows: list<list<string>>}
     */
    public function tableSection(string $heading, ?string $note, array $columns, array $rows): array
    {
        return [
            'heading' => $heading,
            'note' => $note,
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{heading: string, note: string|null, columns: list<string>, rows: list<list<string>>}
     */
    public function metricSection(string $heading, ?string $note, array $metrics): array
    {
        $rows = [];

        foreach ($metrics as $metric => $value) {
            $rows[] = [(string) $metric, self::text($value)];
        }

        return $this->tableSection($heading, $note, ['metric', 'value'], $rows);
    }

    /**
     * Display text for one value. Null reads as an em dash (never zero);
     * booleans spell out; anything else stringifies untouched — Blade
     * escaping, not this method, neutralizes markup.
     */
    public static function text(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_float($value)) {
            return rtrim(rtrim(sprintf('%.2f', $value), '0'), '.');
        }

        return (string) $value;
    }

    /**
     * @param  array{title: string, meta: list<array{label: string, value: string}>, sections: list<array{heading: string, note: string|null, columns: list<string>, rows: list<list<string>>}>}  $document
     */
    public function render(array $document): string
    {
        $rows = 0;
        foreach ($document['sections'] as $section) {
            $rows += count($section['rows']);
        }

        if ($rows > 1000) {
            abort(422, 'This report is too large for PDF. Narrow the filters or use CSV.');
        }

        return View::make('exports.pdf.report', ['document' => $document])->render();
    }

    public function pdf(string $html): string
    {
        if (strlen($html) > 1048576) {
            abort(422, 'This report is too large for PDF. Narrow the filters or use CSV.');
        }

        return Pdf::loadHTML($html)->setOptions($this->options(), true)->output();
    }

    /**
     * @param  array<string, mixed>  $period
     */
    public function periodLabel(array $period): string
    {
        $from = $period['from'] ?? null;
        $to = $period['to_exclusive'] ?? null;

        if (! is_string($from)) {
            $from = null;
        }

        if (! is_string($to)) {
            $to = null;
        }

        if ($from === null && $to === null) {
            return 'All time';
        }

        return ($from ?? '…').' to '.($to ?? '…');
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{title: string, meta: list<array{label: string, value: string}>, sections: list<array{heading: string, note: string|null, columns: list<string>, rows: list<list<string>>}>}
     */
    public function studentProgressView(array $report): array
    {
        /** @var array<string, mixed> $period */
        $period = $report['period'];

        return $this->document('Student Progress Report', [
            ['label' => 'Student ID', 'value' => self::text($report['student_id'])],
            ['label' => 'Period', 'value' => $this->periodLabel($period)],
        ], [
            $this->tableSection('Courses', 'Cumulative current state.', $this->courseColumns(), array_values(array_map($this->courseRow(...), $report['progress']['courses']))),
            $this->tableSection('Skills', 'Cumulative current state.', $this->skillColumns(), array_values(array_map($this->skillRow(...), $report['competency']['skills']))),
            $this->metricSection('Challenges', 'All time when unfiltered; selected period when a date range is supplied.', $this->overallMetrics($report['challenges'])),
            $this->metricSection('Assessments', 'All time when unfiltered; selected period when a date range is supplied.', $this->overallMetrics($report['assessments'])),
            $this->metricSection('Period movement', 'Selected-period movement.', $period),
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{title: string, meta: list<array{label: string, value: string}>, sections: list<array{heading: string, note: string|null, columns: list<string>, rows: list<list<string>>}>}
     */
    public function teacherStudentView(array $report): array
    {
        /** @var array<string, mixed> $period */
        $period = $report['period'];

        return $this->document('Teacher Student Report', [
            ['label' => 'Teacher ID', 'value' => self::text($report['teacher_id'])],
            ['label' => 'Student ID', 'value' => self::text($report['student_id'])],
            ['label' => 'Authorized courses', 'value' => implode(', ', array_map(strval(...), $report['course_ids']))],
            ['label' => 'Period', 'value' => $this->periodLabel($period)],
        ], [
            $this->tableSection('Courses', 'Cumulative current state.', $this->courseColumns(), array_values(array_map($this->courseRow(...), $report['progress']['courses']))),
            $this->tableSection('Skills', 'Cumulative current state.', $this->skillColumns(), array_values(array_map($this->skillRow(...), $report['competency']['skills']))),
            $this->metricSection('Challenges', 'All time when unfiltered; selected period when a date range is supplied.', $this->overallMetrics($report['challenges'])),
            $this->metricSection('Assessments', 'All time when unfiltered; selected period when a date range is supplied.', $this->overallMetrics($report['assessments'])),
            $this->tableSection(
                'Recommendations',
                'Current advisory cards.',
                ['slot', 'title', 'subtitle', 'call to action'],
                array_values(array_map(fn (array $row): array => [
                    self::text($row['slot']),
                    self::text($row['title']),
                    self::text($row['subtitle']),
                    self::text($row['cta']),
                ], $report['recommendations'])),
            ),
            $this->metricSection('Period movement', 'Selected-period movement.', $period),
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{title: string, meta: list<array{label: string, value: string}>, sections: list<array{heading: string, note: string|null, columns: list<string>, rows: list<list<string>>}>}
     */
    public function teacherCourseView(array $report): array
    {
        /** @var array<string, mixed> $period */
        $period = $report['period'];
        /** @var array<string, mixed> $course */
        $course = $report['course'];
        /** @var array<string, mixed> $lifecycle */
        $lifecycle = $report['lifecycle'];
        /** @var array<string, mixed> $challenges */
        $challenges = $report['challenges'];
        /** @var array<string, mixed> $assessments */
        $assessments = $report['assessments'];

        return $this->document('Teacher Course Report', [
            ['label' => 'Teacher ID', 'value' => self::text($report['teacher_id'])],
            ['label' => 'Course', 'value' => self::text($course['name']).' (#'.self::text($course['course_id']).')'],
            ['label' => 'Participating students', 'value' => self::text($lifecycle['participating'])],
            ['label' => 'Period', 'value' => $this->periodLabel($period)],
        ], [
            $this->metricSection('Lifecycle', 'Cumulative current state. Lifecycle buckets are mutually exclusive and sum to the participating population.', $lifecycle),
            $this->tableSection(
                'Competency by student',
                'Cumulative current state per student.',
                ['student', 'state', 'percent'],
                array_values(array_map(fn (array $row): array => [
                    self::text($row['student_id']),
                    self::text($row['state']),
                    self::text($row['percent']),
                ], $report['competency']['students'])),
            ),
            $this->metricSection('Challenges', 'All time when unfiltered; selected period when a date range is supplied.', $this->overallMetrics($challenges)),
            $this->tableSection('Challenges by mission', 'Same basis as the challenge summary above.', $this->challengeColumns(), array_values(array_map($this->challengeRow(...), $challenges['by_challenge']))),
            $this->metricSection('Assessments', 'All time when unfiltered; selected period when a date range is supplied.', $this->overallMetrics($assessments)),
            $this->tableSection('Assessments by Boss Challenge', 'Same basis as the assessment summary above.', $this->assessmentColumns(), array_values(array_map($this->assessmentRow(...), $assessments['by_assessment']))),
            $this->metricSection('Period movement', 'Selected-period movement.', $period),
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{title: string, meta: list<array{label: string, value: string}>, sections: list<array{heading: string, note: string|null, columns: list<string>, rows: list<list<string>>}>}
     */
    public function adminSystemView(array $report): array
    {
        /** @var array<string, mixed> $period */
        $period = $report['period'];
        /** @var array<string, mixed> $users */
        $users = $report['users'];
        /** @var array<string, mixed> $catalog */
        $catalog = $report['catalog'];
        /** @var array<string, mixed> $learning */
        $learning = $report['learning'];
        /** @var array<string, mixed> $gamification */
        $gamification = $report['gamification'];
        /** @var array<string, mixed> $challenges */
        $challenges = $report['challenges'];
        /** @var array<string, mixed> $assessments */
        $assessments = $report['assessments'];

        $userMetrics = ['scope' => $users['scope'], 'total' => $users['total'], 'active' => $users['active'], 'inactive' => $users['inactive']];

        foreach ($users['by_role'] as $role => $count) {
            $userMetrics["role.{$role}"] = $count;
        }

        $gamificationMetrics = $gamification;
        unset($gamificationMetrics['by_type']);

        return $this->document('Admin System Report', [
            ['label' => 'Scope', 'value' => 'Fleet-wide'],
            ['label' => 'Period', 'value' => $this->periodLabel($period)],
        ], [
            $this->metricSection('Users', 'Fleet account facts, current state.', $userMetrics),
            $this->metricSection('Catalog', 'Fleet content facts, current state.', $catalog),
            $this->metricSection(
                'Learning',
                'Cumulative current state. Active students follow the fixed authoritative activity window below, independent of the selected period.',
                [...$learning, 'active_students_window' => self::text($learning['active_students_window_days']).' days']
            ),
            $this->metricSection('Challenges', 'All time when unfiltered; selected period when a date range is supplied.', $this->overallMetrics($challenges)),
            $this->tableSection('Challenges by mission', 'Same basis as the challenge summary above.', $this->challengeColumns(), array_values(array_map($this->challengeRow(...), $challenges['by_challenge']))),
            $this->metricSection('Assessments', 'All time when unfiltered; selected period when a date range is supplied.', $this->overallMetrics($assessments)),
            $this->tableSection('Assessments by Boss Challenge', 'Same basis as the assessment summary above.', $this->assessmentColumns(), array_values(array_map($this->assessmentRow(...), $assessments['by_assessment']))),
            $this->metricSection('Gamification', 'Fleet all-time totals.', $gamificationMetrics),
            $this->metricSection('Period movement', 'Selected-period movement.', $period),
        ]);
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    private function overallMetrics(array $summary): array
    {
        $overall = $summary;
        unset($overall['by_challenge'], $overall['by_assessment']);

        return $overall;
    }

    /**
     * @return list<string>
     */
    private function courseColumns(): array
    {
        return ['course', 'name', 'state', 'percent', 'completed', 'total', 'challenge passed'];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function courseRow(array $row): array
    {
        return [
            self::text($row['course_id']),
            self::text($row['name']),
            self::text($row['state']),
            self::text($row['percent']),
            self::text($row['completed_missions']),
            self::text($row['total_missions']),
            self::text($row['challenge_passed']),
        ];
    }

    /**
     * @return list<string>
     */
    private function skillColumns(): array
    {
        return ['key', 'label', 'percentage', 'state', 'weak', 'correct', 'total', 'completed', 'applicable'];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function skillRow(array $row): array
    {
        return [
            self::text($row['key']),
            self::text($row['label']),
            self::text($row['percentage']),
            self::text($row['state']),
            self::text($row['weak']),
            self::text($row['kc_correct']),
            self::text($row['kc_total']),
            self::text($row['challenges_completed']),
            self::text($row['challenges_applicable']),
        ];
    }

    /**
     * @return list<string>
     */
    private function challengeColumns(): array
    {
        return ['mission', 'course', 'title', 'difficulty', 'attempts', 'completions', 'failures', 'completion rate', 'average attempts', 'failure rate'];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function challengeRow(array $row): array
    {
        return [
            self::text($row['mission_id']),
            self::text($row['course_id']),
            self::text($row['title']),
            self::text($row['difficulty']),
            self::text($row['attempts']),
            self::text($row['completions']),
            self::text($row['failures']),
            self::text($row['completion_rate']),
            self::text($row['average_attempts']),
            self::text($row['failure_rate']),
        ];
    }

    /**
     * @return list<string>
     */
    private function assessmentColumns(): array
    {
        return ['assessment', 'course', 'title', 'attempts', 'passes', 'failures', 'average score', 'pass rate', 'retries', 'completion'];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function assessmentRow(array $row): array
    {
        return [
            self::text($row['assessment_id']),
            self::text($row['course_id']),
            self::text($row['title']),
            self::text($row['attempts']),
            self::text($row['passes']),
            self::text($row['failures']),
            self::text($row['average_score']),
            self::text($row['pass_rate']),
            self::text($row['retries']),
            self::text($row['completion']),
        ];
    }
}
