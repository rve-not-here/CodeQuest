@extends('layouts.app', ['role' => $role])

@section('title', 'Activity')

@section('content')
    @php
        $marks = [
            'mission_completed' => ['check', 'pass'],
            'assessment_completed' => ['check', 'pass'],
            'assessment_passed' => ['check', 'pass'],
            'wrong_submission' => ['x', 'fail'],
            'assessment_failed' => ['x', 'fail'],
            'knowledge_check_completed' => ['circle-question-mark', 'info'],
            'section_completed' => ['flag', 'ink'],
            'hint_used' => ['lightbulb', 'note'],
            'solution_revealed' => ['eye', 'note'],
        ];
        $typeLabels = [];
        foreach ($filterTypes as $type) {
            $typeLabels[$type] = ucfirst(str_replace('_', ' ', $type));
        }
        $typeFilter = $filters['type'] ?? null;
    @endphp
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · {{ $role === 'teacher' ? 'Classroom scope' : 'Platform scope' }}</span>
            @if ($role !== 'teacher')
                <a href="{{ route('students') }}">Students →</a>
            @endif
        </div>

        <header class="atlas-records-heading">
            <h1 class="atlas-records-title">Learning activity</h1>
            <p class="atlas-records-lede">Recorded learning events in chronological order, newest first.</p>
            <dl class="log-facts atlas-records-facts" aria-label="Activity scope">
                <div><dt>Events</dt><dd class="is-numeric">{{ $events->total() }}</dd></div>
                <div><dt>Window</dt><dd>{{ $filters['from'] }} → {{ $filters['to'] }}</dd></div>
                <div><dt>Scope</dt><dd>{{ $role === 'teacher' ? 'Classroom' : 'Platform' }}</dd></div>
                <div><dt>Type filter</dt><dd>{{ $typeFilter !== null && $typeFilter !== '' ? ($typeLabels[$typeFilter] ?? $typeFilter) : 'All types' }}</dd></div>
            </dl>
        </header>

        <form method="GET" action="{{ route('activity') }}" class="atlas-index-filters atlas-index-filters--wide" aria-label="Learning activity filters">
            <div class="atlas-index-filter">
                <label for="student">Student</label>
                <select id="student" name="student" class="terminal-input">
                    <option value="">All students</option>
                    @foreach ($filterStudents as $student)
                        <option value="{{ $student->id }}" @selected(($filters['student'] ?? null) == $student->id)>{{ $student->username }}</option>
                    @endforeach
                </select>
            </div>
            <div class="atlas-index-filter">
                <label for="course">Course</label>
                <select id="course" name="course" class="terminal-input">
                    <option value="">All courses</option>
                    @foreach ($filterCourses as $course)
                        <option value="{{ $course->id }}" @selected(($filters['course'] ?? null) == $course->id)>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="atlas-index-filter">
                <label for="type">Event type</label>
                <select id="type" name="type" class="terminal-input">
                    <option value="">All types</option>
                    @foreach ($filterTypes as $type)
                        <option value="{{ $type }}" @selected($typeFilter === $type)>{{ $typeLabels[$type] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="atlas-index-filter">
                <label for="from">From</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] }}" class="terminal-input">
            </div>
            <div class="atlas-index-filter">
                <label for="to">To</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] }}" class="terminal-input">
            </div>
            <div class="atlas-index-filter-actions">
                <button type="submit" class="btn btn-primary">Filter →</button>
                <a href="{{ route('activity') }}" class="btn btn-secondary">Clear</a>
            </div>
        </form>

        @if ($errors->any())
            <div class="atlas-records-alerts">
                <div class="panel border-accent-line bg-accent-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-accent">Query rejected</strong>
                    @foreach ($errors->all() as $error)
                        <span class="text-fg-muted">{{ $error }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($events->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="empty-activity-title">
                <h2 id="empty-activity-title" class="atlas-records-empty-title">No activity</h2>
                <p class="atlas-records-empty-copy">No learning events match the current scan parameters.</p>
            </section>
        @else
            @foreach ($events->groupBy(fn (array $event): string => $event['at']->format('Y-m-d')) as $date => $dailyEvents)
                <section class="atlas-record-group" aria-labelledby="activity-day-{{ $date }}">
                    <div class="atlas-record-group-head">
                        <h2 id="activity-day-{{ $date }}" class="atlas-record-group-title"><time datetime="{{ $date }}">{{ $dailyEvents->first()['at']->format('F j, Y') }}</time></h2>
                        <p class="atlas-record-group-figure">{{ $dailyEvents->count() }} {{ Str::plural('event', $dailyEvents->count()) }}</p>
                    </div>
                    <ol class="atlas-timeline-list">
                        @foreach ($dailyEvents as $event)
                            @php([$mark, $tone] = $marks[$event['type']] ?? ['·', 'ink'])
                            <li class="atlas-timeline-event" data-event-type="{{ $event['type'] }}">
                                <span class="atlas-timeline-mark atlas-timeline-mark--{{ $tone }}" aria-hidden="true"><x-icon :name="$mark" size="14" /></span>
                                <div class="min-w-0">
                                    <p class="atlas-timeline-label"><a href="{{ route('student-progress', ['student' => $event['user']['id']]) }}">{{ $event['user']['username'] }}</a> · {{ $event['label'] }}</p>
                                    <p class="atlas-timeline-time">
                                        <time datetime="{{ $event['at']->toIso8601String() }}">{{ $event['at']->format('M d, Y · H:i') }}</time>
                                        · {{ $typeLabels[$event['type']] ?? str_replace('_', ' ', $event['type']) }}
                                    </p>
                                </div>
                                @if ($event['pts'] !== null && $event['pts'] !== 0)
                                    <span class="atlas-timeline-points atlas-timeline-points--{{ $event['pts'] > 0 ? 'credit' : 'debit' }}">{{ $event['pts'] > 0 ? '+' : '' }}{{ $event['pts'] }} XP</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endforeach

            @if ($events->hasPages())
                <nav class="atlas-records-pager" aria-label="Learning activity pages">
                    <p class="atlas-records-pager-figure">Showing {{ $events->firstItem() }}–{{ $events->lastItem() }} of {{ $events->total() }} · Page {{ $events->currentPage() }} of {{ $events->lastPage() }}</p>
                    <div class="atlas-records-pager-actions">
                        @if ($events->onFirstPage())
                            <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">← Previous</span>
                        @else
                            <a href="{{ $events->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Previous</a>
                        @endif
                        @if ($events->hasMorePages())
                            <a href="{{ $events->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                        @else
                            <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">Next →</span>
                        @endif
                    </div>
                </nav>
            @endif
        @endif
    </div>
@endsection
