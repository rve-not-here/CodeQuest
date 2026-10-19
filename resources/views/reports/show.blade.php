@extends('layouts.app', ['role' => auth()->user()->role])

@section('title', $document['title'])

@section('content')
    @php
        $teacherReport = auth()->user()->role === 'teacher';
        $adminReport = auth()->user()->role === 'admin' && request()->routeIs('reports.admin-system');
        $studentReport = ! $teacherReport && ! $adminReport;
        $period = collect($document['meta'])->firstWhere('label', 'Period')['value'] ?? 'All time';
    @endphp
    @if ($studentReport)
        <div class="atlas-records">
            <div class="log-running atlas-records-running">
                <span>Records · Progress report · {{ $period }}</span>
                <a href="{{ route('timeline') }}">Learning timeline →</a>
            </div>

            <header class="atlas-records-heading">
                <span class="atlas-lesson-entry-label">Records · Evidence</span>
                <h1 class="atlas-records-title">{{ $document['title'] }}</h1>
                <p class="atlas-records-lede">Your recorded evidence. Each section names its time window.</p>
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

            <form method="GET" class="atlas-index-filters" aria-label="Report filters">
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
                        <p class="atlas-report-empty">No evidence matches this section's scope and filters.</p>
                    @else
                        <div class="atlas-report-table-wrap">
                            <table class="table-stack atlas-report-table" role="table">
                                <thead>
                                    <tr>
                                        @foreach ($section['columns'] as $column)
                                            <th scope="col">{{ $column }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($section['rows'] as $row)
                                        <tr>
                                            @foreach ($row as $index => $cell)
                                                <td data-label="{{ $section['columns'][$index] }}">{{ $cell }}</td>
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
    <div @if ($teacherReport) class="teacher-operations" @elseif ($adminReport) class="admin-management" @endif>
    @if ($teacherReport)
        <header class="teacher-context">
            <div><h1>{{ $document['title'] }}</h1><p>Classroom scope · Server-generated evidence. Each section names its time window.</p></div>
            <a href="{{ route('reports.teacher') }}" class="btn-ghost">All reports →</a>
        </header>
    @elseif ($adminReport)
        <x-page-header :title="$document['title']" subtitle="Server-generated evidence within your authorized scope. Each section names its time window.">
            <x-slot:actions>
                <a href="{{ route('admin.system') }}" class="btn-ghost">System status →</a>
            </x-slot:actions>
        </x-page-header>
    @endif
    @if ($errors->any())
        <x-status-message type="error" title="Check report filters">{{ $errors->first() }}</x-status-message>
    @endif
    <section class="{{ $teacherReport ? 'teacher-report-controls' : 'admin-report-controls' }}" aria-label="Report filters and downloads">
        <form method="GET" class="{{ $teacherReport ? 'teacher-operational-filters' : 'admin-filters admin-report-filters' }}">
            <div><label for="report-from" class="block text-sm">From date</label><input id="report-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="terminal-input"></div>
            <div><label for="report-to" class="block text-sm">To date</label><input id="report-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="terminal-input"></div>
            <div><label for="report-course" class="block text-sm">Course</label><select id="report-course" name="course_id" class="terminal-input"><option value="">All permitted courses</option>@foreach ($courseOptions as $courseOption)<option value="{{ $courseOption->id }}" @selected(($filters['course_id'] ?? '') == $courseOption->id)>{{ $courseOption->name }}</option>@endforeach</select></div>
            @if ($studentOptions->isNotEmpty())
                <div><label for="report-student" class="block text-sm">Student</label><select id="report-student" name="student_id" class="terminal-input"><option value="">All permitted students</option>@foreach ($studentOptions as $studentOption)<option value="{{ $studentOption->id }}" @selected(($filters['student_id'] ?? '') == $studentOption->id)>{{ $studentOption->username }}</option>@endforeach</select></div>
            @elseif (isset($filters['student_id']))
                <input type="hidden" name="student_id" value="{{ $filters['student_id'] }}">
            @endif
            <button class="btn btn-primary" type="submit">APPLY FILTERS</button>
            <a href="{{ url()->current() }}" class="btn btn-ghost">RESET FILTERS</a>
        </form>
        <div class="{{ $teacherReport ? 'teacher-report-downloads' : 'mt-4 flex flex-wrap items-end gap-4' }}">
            <form method="GET" action="{{ route($exportRoute, $exportParams) }}" class="flex flex-wrap items-end gap-3">
                @foreach ($filters as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                <input type="hidden" name="format" value="csv">
                <div><label for="report-dataset" class="block text-sm">CSV dataset</label><select id="report-dataset" name="dataset" class="terminal-input">@foreach ($datasets as $dataset)<option value="{{ $dataset }}">{{ ucfirst($dataset) }}</option>@endforeach</select></div>
                <button type="submit" class="btn btn-secondary">Download CSV</button>
            </form>
            <a href="{{ route($exportRoute, [...$exportParams, ...$filters, 'format' => 'pdf']) }}" class="btn btn-secondary">Download PDF</a>
        </div>
    </section>
    <dl class="{{ $teacherReport ? 'teacher-report-scope' : 'admin-report-scope' }}" aria-label="Report scope">
        @foreach ($document['meta'] as $meta)
            <div class="{{ $teacherReport ? 'teacher-scope-record' : 'admin-property' }}"><dt class="text-sm text-fg-subtle">{{ $meta['label'] }}</dt><dd class="mt-1 break-words font-mono text-sm">{{ $meta['value'] }}</dd></div>
        @endforeach
    </dl>
    @foreach ($document['sections'] as $section)
        <section class="{{ $teacherReport ? 'teacher-record-section' : 'admin-record-section' }}" aria-labelledby="report-section-{{ $loop->index }}">
            <h2 id="report-section-{{ $loop->index }}" class="text-lg font-semibold">{{ $section['heading'] }}</h2>
            @if ($section['note'])<p class="my-3 text-sm text-fg-muted">{{ $section['note'] }}</p>@endif
            @if ($section['rows'] === [])
                <p class="mt-3 text-sm text-fg-subtle">No evidence matches this section's scope and filters.</p>
            @else
                <table class="{{ $teacherReport ? 'table-stack teacher-evidence-table' : 'table-stack admin-table' }}" role="table">
                    <caption>{{ $section['heading'] }}</caption>
                    <thead><tr>@foreach ($section['columns'] as $column)<th scope="col" class="border-b border-line px-3 py-2">{{ $column }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($section['rows'] as $row)
                            <tr>
                                @foreach ($row as $index => $cell)
                                    @if ($index === 0)
                                        <th scope="row" data-label="{{ $section['columns'][$index] }}">{{ $cell }}</th>
                                    @else
                                        <td data-label="{{ $section['columns'][$index] }}" class="break-words border-b border-line px-3 py-2">{{ $cell }}</td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endforeach
    </div>
    @endif
@endsection
