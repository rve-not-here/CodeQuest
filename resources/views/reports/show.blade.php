@extends('layouts.app', ['role' => auth()->user()->role])

@section('title', $document['title'])

@section('content')
    @php
        $viewer = auth()->user();
        $role = $viewer->role;
        $teacherReport = $role === 'teacher';
        $studentReport = $role === 'student';
        $adminReport = $role === 'admin' && request()->routeIs('reports.admin-system');
        $period = collect($document['meta'])->firstWhere('label', 'Period')['value'] ?? 'All time';
        $routeCourse = request()->route('course');
        $routeStudent = request()->route('student');

        $displayTitle = match ($document['title']) {
            'Teacher Course Report' => 'Course report',
            'Teacher Student Report' => 'Student report',
            'Student Progress Report' => 'Progress report',
            'Admin System Report' => 'System report',
            default => $document['title'],
        };

        $lede = match (true) {
            $adminReport => 'Platform-wide evidence from recorded learning and administrative records. Each register names its time window.',
            $role === 'admin' => 'Platform scope. Server-generated evidence; each register names its time window.',
            $teacherReport && $routeStudent !== null => $routeStudent->username.' · Classroom scope. Server-generated evidence; each register names its time window.',
            $teacherReport && $routeCourse !== null => $routeCourse->name.' · Classroom scope. Server-generated evidence; each register names its time window.',
            default => 'Your recorded evidence. Each register names its time window.',
        };

        $courseNames = $courseOptions->keyBy('id');
        $studentNames = $studentOptions->keyBy('id');

        // Meta labels are stable keys from the report document. Resolve the
        // identity rows to human identifiers without changing the document.
        $metaLabels = [
            'Teacher ID' => 'Teacher',
            'Student ID' => 'Student',
            'Authorized courses' => 'Courses in scope',
        ];

        $metaRows = collect($document['meta'])->map(function (array $meta) use ($viewer, $role, $routeStudent, $courseNames, $metaLabels): array {
            $key = $meta['label'];
            $label = $metaLabels[$key] ?? $key;
            $value = $meta['value'];

            // The report document carries record ids. Resolve the identity the
            // reader already knows (their own name, the student they opened,
            // the permitted course names); keep an explicit id only when the
            // reader has no way to resolve it.
            if ($key === 'Teacher ID') {
                $value = $role === 'teacher'
                    ? $viewer->username
                    : ($role === 'admin' ? 'Teacher #'.$value : $value);
            }

            if ($key === 'Student ID') {
                $value = $routeStudent?->username
                    ?? ($role === 'student' ? $viewer->username : 'Student #'.$value);
            }

            if ($key === 'Authorized courses') {
                $names = collect(explode(', ', $value))
                    ->filter()
                    ->map(fn (string $id): string => $courseNames->get((int) $id)?->name ?? 'Course #'.$id)
                    ->all();
                $value = $names === [] ? '—' : implode(', ', $names);
            }

            return [
                'label' => $label,
                'value' => $value,
                'numeric' => preg_match('/^\d+$/', (string) $value) === 1,
            ];
        });

        $sectionTitles = [
            'Challenges' => 'Challenge summary',
            'Assessments' => 'Boss Challenge summary',
            'Assessments by Boss Challenge' => 'Boss Challenges',
            'Gamification' => 'XP and rewards',
        ];

        $sectionKickers = [
            'Lifecycle' => 'Current state',
            'Competency by student' => 'Current state',
            'Challenges' => 'Recorded evidence',
            'Challenges by mission' => 'Recorded evidence',
            'Assessments' => 'Recorded evidence',
            'Assessments by Boss Challenge' => 'Recorded evidence',
            'Period movement' => 'Selected period',
            'Courses' => 'Current state',
            'Skills' => 'Current state',
            'Recommendations' => 'Advisory',
            'Users' => 'Current state',
            'Catalog' => 'Current state',
            'Learning' => 'Current state',
            'Gamification' => 'Lifetime totals',
        ];

        $metricLabels = [
            'participating' => 'Participating students',
            'engaged' => 'Engaged students',
            'not_started' => 'Not started',
            'in_progress' => 'In progress',
            'assessment_ready' => 'Boss ready',
            'completed' => 'Completed course',
            'average_completion' => 'Average completion',
            'pass_rate' => 'Boss pass rate',
            'attempts' => 'Attempts',
            'completions' => 'Completions',
            'failures' => 'Failures',
            'completion_rate' => 'Completion rate',
            'average_attempts' => 'Average attempts',
            'passes' => 'Passed attempts',
            'average_score' => 'Average score',
            'retries' => 'Retries',
            'completion' => 'Students passed',
            'from' => 'From',
            'to_exclusive' => 'To (exclusive)',
            'wrong_submissions' => 'Wrong submissions',
            'assessment_attempts' => 'Assessment attempts',
            'assessment_passes' => 'Assessment passes',
            'scope' => 'Scope',
            'total' => 'Total',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'role.student' => 'Students',
            'role.teacher' => 'Teachers',
            'role.admin' => 'Admins',
            'role.operator' => 'Operators',
            'courses' => 'Courses',
            'sections' => 'Sections',
            'challenges' => 'Challenges',
            'boss_challenges' => 'Boss Challenges',
            'active_students' => 'Active students',
            'active_students_window' => 'Activity window',
            'completed_challenges' => 'Completed challenges',
            'course_completions' => 'Course completions',
            'awarded' => 'XP awarded',
            'spent' => 'XP spent',
            'deducted' => 'XP deducted',
            'outstanding' => 'XP outstanding',
            'accounts' => 'Accounts with XP',
        ];

        $percentageMetrics = ['average_completion', 'completion_rate', 'failure_rate', 'pass_rate', 'percentage', 'percent'];

        $metricLabelFor = fn (string $key): string => $metricLabels[$key]
            ?? ucfirst(str_replace(['.', '_'], [' · ', ' '], $key));

        $metricValueFor = function (string $key, string $raw) use ($percentageMetrics): array {
            $value = (string) $raw;

            if ($key === 'scope') {
                $value = $value === 'fleet' ? 'Platform' : ucfirst($value);
            } elseif (in_array($key, ['from', 'to_exclusive'], true)) {
                $value = $value === '—' ? 'All time' : \Illuminate\Support\Carbon::parse($value)->format('M d, Y · H:i');
            } elseif ($value === 'true') {
                $value = 'Yes';
            } elseif ($value === 'false') {
                $value = 'No';
            } elseif (in_array($key, $percentageMetrics, true) && is_numeric($value)) {
                $value = $value.'%';
            }

            return ['value' => $value, 'numeric' => is_numeric($raw)];
        };

        $columnLabels = [
            'mission' => 'Challenge',
            'course' => 'Course',
            'title' => 'Title',
            'difficulty' => 'Difficulty',
            'attempts' => 'Attempts',
            'completions' => 'Completions',
            'failures' => 'Failures',
            'completion rate' => 'Completion rate',
            'average attempts' => 'Avg attempts',
            'failure rate' => 'Failure rate',
            'assessment' => 'Boss Challenge',
            'passes' => 'Passed attempts',
            'average score' => 'Avg score',
            'pass rate' => 'Pass rate',
            'retries' => 'Retries',
            'completion' => 'Students passed',
            'student' => 'Student',
            'state' => 'State',
            'percent' => 'Completion',
            'key' => 'Skill key',
            'label' => 'Skill',
            'percentage' => 'Score',
            'weak' => 'Weak',
            'correct' => 'Correct answers',
            'total' => 'Questions',
            'completed' => 'Completed',
            'applicable' => 'Applicable',
            'slot' => 'Slot',
            'subtitle' => 'Subtitle',
            'call to action' => 'Action',
            'name' => 'Name',
        ];

        $tableHeadings = [
            'Competency by student' => ['header' => 0, 'header_label' => 'Student', 'type' => 'student', 'skip' => [0]],
            'Challenges by mission' => ['header' => 2, 'header_label' => 'Challenge', 'type' => 'mission', 'skip' => [0, 2]],
            'Assessments by Boss Challenge' => ['header' => 2, 'header_label' => 'Boss Challenge', 'type' => 'assessment', 'skip' => [0, 2]],
            'Courses' => ['header' => 1, 'header_label' => 'Course', 'type' => 'course', 'skip' => [0, 1]],
            'Skills' => ['header' => 1, 'header_label' => 'Skill', 'type' => 'skill', 'skip' => [0, 1]],
            'Recommendations' => ['header' => 1, 'header_label' => 'Recommendation', 'type' => 'recommendation', 'skip' => [0, 1]],
        ];

        $tableColumnLabels = [
            'Competency by student' => [
                'state' => 'State',
                'percent' => 'Completion',
            ],
            'Challenges by mission' => [
                'course' => 'Course',
                'difficulty' => 'Difficulty',
                'attempts' => 'Attempts',
                'completions' => 'Completions',
                'failures' => 'Failures',
                'completion rate' => 'Completion rate',
                'average attempts' => 'Avg attempts',
                'failure rate' => 'Failure rate',
            ],
            'Assessments by Boss Challenge' => [
                'course' => 'Course',
                'attempts' => 'Attempts',
                'passes' => 'Passed attempts',
                'failures' => 'Failures',
                'average score' => 'Avg score',
                'pass rate' => 'Pass rate',
                'retries' => 'Retries',
                'completion' => 'Students passed',
            ],
            'Courses' => [
                'state' => 'State',
                'percent' => 'Completion',
                'completed' => 'Missions completed',
                'total' => 'Missions total',
                'challenge passed' => 'Boss passed',
            ],
            'Skills' => [
                'percentage' => 'Score',
                'state' => 'State',
                'weak' => 'Weak',
                'correct' => 'Correct answers',
                'total' => 'Questions',
                'completed' => 'Challenges completed',
                'applicable' => 'Challenges applicable',
            ],
            'Recommendations' => [
                'subtitle' => 'Subtitle',
                'call to action' => 'Action',
            ],
        ];

        $cellFor = function (string $column, string $raw) use ($studentNames, $courseNames): array {
            $value = (string) $raw;

            if ($value === 'true') {
                $value = 'Yes';
            } elseif ($value === 'false') {
                $value = 'No';
            } elseif ($column === 'student') {
                $student = $studentNames->get((int) $value);
                $value = $student?->username ?? 'Student #'.$value;
            } elseif ($column === 'course') {
                $value = $courseNames->get((int) $value)?->name ?? $value;
            } elseif (in_array($column, ['state', 'difficulty'], true)) {
                $value = ucfirst(str_replace('_', ' ', strtolower($value)));
            } elseif (in_array($column, ['percent', 'percentage', 'completion rate', 'failure rate', 'pass rate'], true) && is_numeric($value)) {
                $value = $value.'%';
            }

            return ['value' => $value, 'numeric' => is_numeric($raw)];
        };

        $tableNonNumericColumns = ['course', 'student', 'state', 'difficulty', 'weak', 'challenge passed', 'key', 'label', 'title', 'subtitle', 'call to action'];
    @endphp

    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · {{ $period }}</span>
            <span class="atlas-records-running-links">
                @if ($adminReport)
                    <a href="{{ route('admin.system') }}">System status →</a>
                @elseif ($role === 'student')
                    <a href="{{ route('timeline') }}">Learning timeline →</a>
                @else
                    <a href="{{ route('reports.teacher') }}">All reports →</a>
                @endif
            </span>
        </div>

        <header class="atlas-records-heading">
            <h1 class="atlas-records-title">{{ $displayTitle }}</h1>
            <p class="atlas-records-lede">{{ $lede }}</p>
            <dl class="log-facts atlas-records-facts" aria-label="Report scope">
                @foreach ($metaRows as $meta)
                    <div>
                        <dt>{{ $meta['label'] }}</dt>
                        <dd @class(['is-numeric' => $meta['numeric']])>{{ $meta['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </header>

        <nav class="atlas-record-entry-actions" aria-label="Report sections">
            <span class="atlas-record-entry-label">In this report</span>
            @foreach ($document['sections'] as $section)
                <a href="#report-section-{{ $loop->index }}" class="btn btn-ghost btn-sm">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} {{ $sectionTitles[$section['heading']] ?? $section['heading'] }}</a>
            @endforeach
        </nav>

        @if ($errors->any())
            <div class="atlas-records-alerts">
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Check report filters.</strong>
                    <span class="text-fg-muted">{{ $errors->first() }}</span>
                </div>
            </div>
        @endif

        <form method="GET" class="atlas-index-filters{{ $studentOptions->isNotEmpty() ? ' atlas-index-filters--staff' : '' }}" aria-label="Report filters">
            <div class="atlas-index-filter">
                <label for="report-from" class="atlas-lesson-entry-label">From date</label>
                <input id="report-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="field w-full">
            </div>
            <div class="atlas-index-filter">
                <label for="report-to" class="atlas-lesson-entry-label">To date</label>
                <input id="report-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="field w-full">
            </div>
            <div class="atlas-index-filter">
                <label for="report-course" class="atlas-lesson-entry-label">Course</label>
                <select id="report-course" name="course_id" class="field w-full">
                    <option value="">All permitted courses</option>
                    @foreach ($courseOptions as $courseOption)
                        <option value="{{ $courseOption->id }}" @selected(($filters['course_id'] ?? '') == $courseOption->id)>{{ $courseOption->name }}</option>
                    @endforeach
                </select>
            </div>
            @if ($studentOptions->isNotEmpty())
                <div class="atlas-index-filter">
                    <label for="report-student" class="atlas-lesson-entry-label">Student</label>
                    <select id="report-student" name="student_id" class="field w-full">
                        <option value="">All permitted students</option>
                        @foreach ($studentOptions as $studentOption)
                            <option value="{{ $studentOption->id }}" @selected(($filters['student_id'] ?? '') == $studentOption->id)>{{ $studentOption->username }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif (isset($filters['student_id']))
                <input type="hidden" name="student_id" value="{{ $filters['student_id'] }}">
            @endif
            <div class="atlas-index-filter-actions">
                <button class="btn btn-primary" type="submit">Apply filters</button>
                <a href="{{ url()->current() }}" class="btn btn-ghost">Reset filters</a>
            </div>
        </form>

        <div class="atlas-report-exports">
            <form method="GET" action="{{ route($exportRoute, $exportParams) }}" class="atlas-report-export">
                @foreach ($filters as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <input type="hidden" name="format" value="csv">
                <div class="atlas-index-filter">
                    <label for="report-dataset" class="atlas-lesson-entry-label">CSV dataset</label>
                    <select id="report-dataset" name="dataset" class="field w-full">
                        @foreach ($datasets as $dataset)
                            <option value="{{ $dataset }}">{{ ucfirst($dataset) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Download CSV</button>
            </form>
            <a href="{{ route($exportRoute, [...$exportParams, ...$filters, 'format' => 'pdf']) }}" class="btn btn-secondary">Download PDF</a>
        </div>

        @foreach ($document['sections'] as $section)
            @php
                $heading = $sectionTitles[$section['heading']] ?? $section['heading'];
                $kicker = $sectionKickers[$section['heading']] ?? 'Recorded evidence';
                $isMetricSection = $section['columns'] === ['metric', 'value'];
                $tableHeading = $tableHeadings[$section['heading']] ?? null;
                $labels = $tableColumnLabels[$section['heading']] ?? $columnLabels;
                $skip = $tableHeading['skip'] ?? [];
                $metricRows = [];

                if ($isMetricSection) {
                    foreach ($section['rows'] as $row) {
                        $metric = $metricValueFor((string) $row[0], (string) ($row[1] ?? '—'));
                        $metricRows[] = ['label' => $metricLabelFor((string) $row[0]), 'value' => $metric['value'], 'numeric' => $metric['numeric']];
                    }
                }

                $displayedIndexes = array_values(array_filter(
                    array_keys($section['columns']),
                    fn (int $index): bool => ! in_array($index, $skip, true),
                ));

                $numericColumns = [];
                foreach ($displayedIndexes as $index) {
                    $column = $section['columns'][$index];
                    $numericColumns[$index] = ! in_array($column, $tableNonNumericColumns, true)
                        && collect($section['rows'])->every(
                            fn (array $row): bool => isset($row[$index]) && is_numeric($row[$index]),
                        );
                }
            @endphp

            <section class="atlas-record-group" aria-labelledby="report-section-{{ $loop->index }}">
                <div class="atlas-record-group-head">
                    <div>
                        <span class="atlas-record-entry-label">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} · {{ $kicker }}</span>
                        <h2 id="report-section-{{ $loop->index }}" class="atlas-record-group-title">{{ $heading }}</h2>
                    </div>
                    <span class="atlas-record-group-figure">{{ count($section['rows']) }} {{ $isMetricSection ? Str::plural('measure', count($section['rows'])) : Str::plural('row', count($section['rows'])) }}</span>
                </div>
                @if ($section['note'])
                    <p class="atlas-record-group-note">{{ $section['note'] }}</p>
                @endif
                @if ($section['rows'] === [])
                    <p class="atlas-register-empty">No evidence matches this section's scope and filters.</p>
                @elseif ($isMetricSection)
                    <div class="atlas-register-table-wrap">
                        <table class="table-stack atlas-register-table" role="table">
                            <caption>{{ $heading }}</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Measure</th>
                                    <th scope="col">Value</th>
                                    <th scope="col">Measure</th>
                                    <th scope="col">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (array_chunk($metricRows, 2) as $pair)
                                    <tr>
                                        @foreach ($pair as $metric)
                                            <th scope="row">{{ $metric['label'] }}</th>
                                            <td data-label="Value" @class(['is-number' => $metric['numeric']])>{{ $metric['value'] }}</td>
                                        @endforeach
                                        @if (count($pair) === 1)
                                            <td colspan="2"></td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="atlas-register-table-wrap">
                        <table class="table-stack atlas-register-table" role="table">
                            <caption>{{ $heading }}</caption>
                            <thead>
                                <tr>
                                    @if ($tableHeading !== null)
                                        <th scope="col">{{ $tableHeading['header_label'] }}</th>
                                    @else
                                        <th scope="col">{{ $labels[$section['columns'][0]] ?? $section['columns'][0] }}</th>
                                    @endif
                                    @foreach ($displayedIndexes as $index)
                                        <th scope="col" @class(['is-number' => $numericColumns[$index]])>{{ $labels[$section['columns'][$index]] ?? $section['columns'][$index] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($section['rows'] as $row)
                                    @php
                                        $headerIndex = $tableHeading['header'] ?? 0;
                                        $headerRaw = (string) ($row[$headerIndex] ?? '—');
                                        $headerText = $headerRaw;
                                        $headerNote = null;
                                        $student = null;
                                        $course = null;

                                        if ($tableHeading !== null) {
                                            if ($tableHeading['type'] === 'student') {
                                                $student = $studentNames->get((int) $headerRaw);
                                                $headerText = $student?->username ?? 'Student #'.$headerRaw;
                                                $headerNote = 'Student #'.$headerRaw;
                                            } elseif ($tableHeading['type'] === 'mission') {
                                                $headerNote = 'Challenge #'.($row[0] ?? '—');
                                            } elseif ($tableHeading['type'] === 'assessment') {
                                                $headerNote = 'Boss Challenge #'.($row[0] ?? '—');
                                            } elseif ($tableHeading['type'] === 'course') {
                                                $course = $courseNames->get((int) ($row[0] ?? 0));
                                                $headerText = $course?->name ?? $headerRaw;
                                                $headerNote = 'Course #'.($row[0] ?? '—');
                                            } elseif ($tableHeading['type'] === 'skill') {
                                                $headerNote = (string) ($row[0] ?? '—');
                                            } elseif ($tableHeading['type'] === 'recommendation') {
                                                $headerNote = 'Slot '.($row[0] ?? '—');
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <th scope="row" data-label="{{ $tableHeading['header_label'] ?? ($labels[$section['columns'][0]] ?? $section['columns'][0]) }}">
                                            @if ($student !== null && in_array($role, ['teacher', 'admin'], true))
                                                <a href="{{ route('reports.teacher-student', $student) }}">{{ $headerText }}</a>
                                            @elseif ($course !== null && in_array($role, ['teacher', 'admin'], true))
                                                <a href="{{ route('reports.teacher-course', $course) }}">{{ $headerText }}</a>
                                            @else
                                                {{ $headerText }}
                                            @endif
                                            @if ($headerNote !== null)
                                                <span class="atlas-register-note font-mono">{{ $headerNote }}</span>
                                            @endif
                                        </th>
                                        @foreach ($displayedIndexes as $index)
                                            @php
                                                $cell = $cellFor($section['columns'][$index], (string) ($row[$index] ?? '—'));
                                            @endphp
                                            <td data-label="{{ $labels[$section['columns'][$index]] ?? $section['columns'][$index] }}" @class(['is-number' => $numericColumns[$index]])>{{ $cell['value'] }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
@endsection
