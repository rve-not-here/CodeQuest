@extends('layouts.app', ['role' => auth()->user()->role])

@section('title', $document['title'])

@section('content')
    @php
        $teacherReport = auth()->user()->role === 'teacher';
        $adminReport = auth()->user()->role === 'admin' && request()->routeIs('reports.admin-system');
        $period = collect($document['meta'])->firstWhere('label', 'Period')['value'] ?? 'All time';
    @endphp
    @if (! $adminReport)
        <div class="atlas-records">
            <div class="log-running atlas-records-running">
                <span>Records · {{ $period }}</span>
            </div>

            <header class="atlas-records-heading">
                @if ($teacherReport)
                @endif
                <h1 class="atlas-records-title">{{ $document['title'] }}</h1>
                <p class="atlas-records-lede">{{ $teacherReport ? 'Classroom scope · Server-generated evidence. Each section names its time window.' : 'Your recorded evidence. Each section names its time window.' }}</p>
                <dl class="log-facts atlas-records-facts">
                    @foreach ($document['meta'] as $meta)
                        <div>
                            <dt>{{ $meta['label'] }}</dt>
                            <dd class="is-numeric">{{ $meta['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </header>

            @if ($errors->any())
                <div class="atlas-records-alerts">
                    <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                        <strong class="text-danger">Check report filters.</strong>
                        <span class="text-fg-muted">{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            <form method="GET" class="atlas-index-filters{{ $teacherReport && $studentOptions->isNotEmpty() ? ' atlas-index-filters--staff' : '' }}" aria-label="Report filters">
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
                @if ($teacherReport)
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
                @endif
                <div class="atlas-index-filter-actions">
                    <button class="btn btn-primary" type="submit">Apply filters</button>
                    <a href="{{ url()->current() }}" class="btn-ghost">Reset filters</a>
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
                <section class="atlas-record-group" aria-labelledby="report-section-{{ $loop->index }}">
                    <div class="atlas-record-group-head">
                        <div>
                            <span class="atlas-record-entry-label">Register {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h2 id="report-section-{{ $loop->index }}" class="atlas-record-group-title">{{ $section['heading'] }}</h2>
                        </div>
                        <span class="atlas-record-group-figure">{{ count($section['rows']) }} {{ Str::plural('row', count($section['rows'])) }}</span>
                    </div>
                    @if ($section['note'])
                        <p class="atlas-record-group-note">{{ $section['note'] }}</p>
                    @endif
                    @if ($section['rows'] === [])
                        <p class="atlas-register-empty">No evidence matches this section's scope and filters.</p>
                    @else
                        @php
                            // A column is numeric only when every row reads as a number; mixed
                            // evidence columns stay left-aligned.
                            $numericColumns = [];
                            foreach ($section['columns'] as $columnIndex => $column) {
                                $numericColumns[$columnIndex] = $columnIndex > 0 && collect($section['rows'])->every(
                                    fn (array $row): bool => isset($row[$columnIndex]) && is_numeric($row[$columnIndex])
                                );
                            }
                        @endphp
                        <div class="atlas-register-table-wrap">
                            <table class="table-stack atlas-register-table atlas-register-table--evidence" role="table">
                                <caption>{{ $section['heading'] }}</caption>
                                <thead>
                                    <tr>
                                        @foreach ($section['columns'] as $column)
                                            <th scope="col" @class(['is-number' => $numericColumns[$loop->index]])>{{ $column }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($section['rows'] as $row)
                                        <tr>
                                            @foreach ($row as $index => $cell)
                                                @if ($index === 0)
                                                    <th scope="row" data-label="{{ $section['columns'][$index] }}">{{ $cell }}</th>
                                                @else
                                                    <td data-label="{{ $section['columns'][$index] }}" @class(['is-number' => $numericColumns[$index]])>{{ $cell }}</td>
                                                @endif
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
    @else
        <div class="atlas-records">
            <div class="log-running atlas-records-running">
                <span>Records · Platform scope</span>
            </div>

            <header class="atlas-records-heading">
                <h1 class="atlas-records-title">{{ $document['title'] }}</h1>
                <p class="atlas-records-lede">Server-generated evidence across the platform. Each section names its time window.</p>
                <dl class="log-facts atlas-records-facts" aria-label="Report scope">
                    @foreach ($document['meta'] as $meta)
                        <div>
                            <dt>{{ $meta['label'] }}</dt>
                            <dd class="is-numeric">{{ $meta['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </header>

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
                <section class="atlas-record-group" aria-labelledby="report-section-{{ $loop->index }}">
                    <div class="atlas-record-group-head">
                        <div>
                            <span class="atlas-record-entry-label">Register {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h2 id="report-section-{{ $loop->index }}" class="atlas-record-group-title">{{ $section['heading'] }}</h2>
                        </div>
                        <span class="atlas-record-group-figure">{{ count($section['rows']) }} {{ Str::plural('row', count($section['rows'])) }}</span>
                    </div>
                    @if ($section['note'])
                        <p class="atlas-record-group-note">{{ $section['note'] }}</p>
                    @endif
                    @if ($section['rows'] === [])
                        <p class="atlas-register-empty">No evidence matches this section's scope and filters.</p>
                    @else
                        @php
                            // A column is numeric only when every row reads as a number; mixed
                            // evidence columns stay left-aligned.
                            $numericColumns = [];
                            foreach ($section['columns'] as $columnIndex => $column) {
                                $numericColumns[$columnIndex] = $columnIndex > 0 && collect($section['rows'])->every(
                                    fn (array $row): bool => isset($row[$columnIndex]) && is_numeric($row[$columnIndex])
                                );
                            }
                        @endphp
                        <div class="atlas-register-table-wrap">
                            <table class="table-stack atlas-register-table atlas-register-table--evidence" role="table">
                                <caption>{{ $section['heading'] }}</caption>
                                <thead>
                                    <tr>
                                        @foreach ($section['columns'] as $column)
                                            <th scope="col" @class(['is-number' => $numericColumns[$loop->index]])>{{ $column }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($section['rows'] as $row)
                                        <tr>
                                            @foreach ($row as $index => $cell)
                                                @if ($index === 0)
                                                    <th scope="row" data-label="{{ $section['columns'][$index] }}">{{ $cell }}</th>
                                                @else
                                                    <td data-label="{{ $section['columns'][$index] }}" @class(['is-number' => $numericColumns[$index]])>{{ $cell }}</td>
                                                @endif
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
    @endif
@endsection
