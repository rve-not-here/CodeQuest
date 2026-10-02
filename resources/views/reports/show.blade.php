@extends('layouts.app', ['role' => auth()->user()->role])

@section('title', $document['title'])

@section('content')
    <x-page-header :title="$document['title']" subtitle="Server-generated evidence within your authorized scope. Each section names its time window." icon="Σ" />
    @if ($errors->any())
        <x-status-message type="error" title="Check report filters">{{ $errors->first() }}</x-status-message>
    @endif
    <section class="panel mb-5 p-5" aria-label="Report filters and downloads">
        <form method="GET" class="flex flex-wrap items-end gap-4">
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
        <div class="mt-4 flex flex-wrap items-end gap-4">
            <form method="GET" action="{{ route($exportRoute, $exportParams) }}" class="flex flex-wrap items-end gap-3">
                @foreach ($filters as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                <input type="hidden" name="format" value="csv">
                <div><label for="report-dataset" class="block text-sm">CSV dataset</label><select id="report-dataset" name="dataset" class="terminal-input">@foreach ($datasets as $dataset)<option value="{{ $dataset }}">{{ ucfirst($dataset) }}</option>@endforeach</select></div>
                <button type="submit" class="btn btn-secondary">DOWNLOAD CSV</button>
            </form>
            <a href="{{ route($exportRoute, [...$exportParams, ...$filters, 'format' => 'pdf']) }}" class="btn btn-secondary">DOWNLOAD PDF</a>
        </div>
    </section>
    <dl class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-2">
        @foreach ($document['meta'] as $meta)
            <div class="panel p-4"><dt class="text-sm text-fg-subtle">{{ $meta['label'] }}</dt><dd class="mt-1 break-words font-mono text-sm">{{ $meta['value'] }}</dd></div>
        @endforeach
    </dl>
    @foreach ($document['sections'] as $section)
        <section class="panel mb-5 p-4" aria-labelledby="report-section-{{ $loop->index }}">
            <h2 id="report-section-{{ $loop->index }}" class="text-lg font-semibold">{{ $section['heading'] }}</h2>
            @if ($section['note'])<p class="my-3 text-sm text-fg-muted">{{ $section['note'] }}</p>@endif
            @if ($section['rows'] === [])
                <p class="mt-3 text-sm text-fg-subtle">No evidence matches this section's scope and filters.</p>
            @else
                <table class="table-stack w-full text-left text-sm">
                    <thead><tr>@foreach ($section['columns'] as $column)<th scope="col" class="border-b border-line px-3 py-2">{{ $column }}</th>@endforeach</tr></thead>
                    <tbody>@foreach ($section['rows'] as $row)<tr>@foreach ($row as $index => $cell)<td data-label="{{ $section['columns'][$index] }}" class="break-words border-b border-line px-3 py-2">{{ $cell }}</td>@endforeach</tr>@endforeach</tbody>
                </table>
            @endif
        </section>
    @endforeach
@endsection
