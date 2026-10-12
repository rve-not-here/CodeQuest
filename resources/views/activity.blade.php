@extends('layouts.app', ['role' => $role])

@section('title', 'Activity')

@section('content')
    <div class="teacher-operations">
        <header class="teacher-context">
            <div><h1>Learning Activity</h1><p>{{ $role === 'teacher' ? 'Classroom scope' : 'SYSTEM-WIDE' }} · Recorded learning events in chronological order, newest first.</p></div>
            <a href="{{ route('students') }}" class="btn-ghost">Student roster →</a>
        </header>
        <form method="GET" action="{{ route('activity') }}" class="teacher-operational-filters" aria-label="Learning activity filters">
            <div><label for="student">Student</label><select id="student" name="student" class="terminal-input"><option value="">ALL STUDENTS</option>@foreach ($filterStudents as $student)<option value="{{ $student->id }}" @selected(($filters['student'] ?? null) == $student->id)>{{ $student->username }}</option>@endforeach</select></div>
            <div><label for="course">Course</label><select id="course" name="course" class="terminal-input"><option value="">ALL COURSES</option>@foreach ($filterCourses as $course)<option value="{{ $course->id }}" @selected(($filters['course'] ?? null) == $course->id)>{{ $course->name }}</option>@endforeach</select></div>
            <div><label for="type">Event type</label><select id="type" name="type" class="terminal-input"><option value="">ALL TYPES</option>@foreach ($filterTypes as $type)<option value="{{ $type }}" @selected(($filters['type'] ?? null) === $type)>{{ strtoupper(str_replace('_', ' ', $type)) }}</option>@endforeach</select></div>
            <div><label for="from">From</label><input id="from" name="from" type="date" value="{{ $filters['from'] }}" class="terminal-input"></div>
            <div><label for="to">To</label><input id="to" name="to" type="date" value="{{ $filters['to'] }}" class="terminal-input"></div>
            <div class="teacher-filter-actions">
                <button type="submit" class="btn-primary">FILTER →</button>
                @if (! empty($filters['student']) || ! empty($filters['course']) || ! empty($filters['type']) || ($filters['from'] ?? '') !== '' || ($filters['to'] ?? '') !== '')
                    <a href="{{ route('activity') }}" class="btn-ghost">CLEAR</a>
                @endif
            </div>
            @if ($errors->any())
                <x-status-message type="error" title="QUERY REJECTED">
                    @foreach ($errors->all() as $error){{ $error }}<br>@endforeach
                </x-status-message>
            @endif
        </form>
        <section class="teacher-record-section" aria-labelledby="activity-title">
            <header class="teacher-section-heading">
                <div><h2 id="activity-title">EVENT LOG</h2><p class="teacher-state">WINDOW {{ $filters['from'] }} → {{ $filters['to'] }}</p></div>
                <p class="teacher-state">{{ $events->total() }} EVENTS</p>
            </header>
            @if ($events->isEmpty())
                <div class="teacher-empty"><h3>NO ACTIVITY</h3><p>No learning events match the current scan parameters.</p></div>
            @else
                <table class="table-stack teacher-evidence-table" role="table">
                    <caption>Learning events in the selected time window</caption>
                    <thead><tr><th scope="col">Recorded at</th><th scope="col">Student</th><th scope="col">Event type</th><th scope="col">Evidence</th><th scope="col">XP change</th></tr></thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <td data-label="Recorded at"><time class="teacher-state" datetime="{{ $event['at']->toIso8601String() }}">{{ $event['at']->format('Y-m-d H:i') }}</time></td>
                                <th scope="row" data-label="Student"><a href="{{ route('student-progress', ['student' => $event['user']['id']]) }}">{{ $event['user']['username'] }}</a></th>
                                <td data-label="Event type"><span class="teacher-state">{{ str_replace('_', ' ', $event['type']) }}</span></td>
                                <td data-label="Evidence">{{ $event['label'] }}</td>
                                <td data-label="XP change"><span class="teacher-state">{{ $event['pts'] !== null && $event['pts'] !== 0 ? ($event['pts'] > 0 ? '+' : '').$event['pts'].' XP' : '—' }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($events->hasPages())
                    <nav class="teacher-pagination" aria-label="Learning activity pages">
                        <p>SHOWING PAGE {{ $events->currentPage() }} OF {{ $events->lastPage() }}</p>
                        <div>
                            @if ($events->onFirstPage())<span class="teacher-page-disabled">◀ PREV</span>@else<a href="{{ $events->previousPageUrl() }}" class="btn-ghost">◀ PREV</a>@endif
                            @if ($events->hasMorePages())<a href="{{ $events->nextPageUrl() }}" class="btn-ghost">NEXT ▶</a>@else<span class="teacher-page-disabled">NEXT ▶</span>@endif
                        </div>
                    </nav>
                @endif
            @endif
        </section>
    </div>
@endsection
