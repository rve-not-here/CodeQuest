@extends('layouts.app', ['role' => $role])

@section('title', 'Students')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Students · {{ $role === 'teacher' ? 'Classroom scope' : 'Platform scope' }}</span>
            @if ($role === 'teacher')
                <a href="{{ route('classrooms') }}">My classrooms →</a>
            @else
                <span class="atlas-stamp atlas-stamp--ink">System-wide</span>
            @endif
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Roster</span>
            <h1 class="atlas-records-title">Students</h1>
            <p class="atlas-records-lede">{{ $role === 'teacher' ? 'Monitor students and assigned courses in your active classrooms.' : 'Monitor student progress across all courses.' }}</p>
            <dl class="log-facts atlas-records-facts" aria-label="Roster scope">
                <div><dt>Total students</dt><dd class="is-numeric">{{ $dashboardMetrics['total_students'] }}</dd></div>
                <div><dt>Active · 14d</dt><dd class="is-numeric"><a href="{{ route('activity') }}">{{ $dashboardMetrics['active_students'] }}<span class="sr-only"> active students, view learning activity</span></a></dd></div>
                <div><dt>Courses in progress</dt><dd class="is-numeric"><a href="{{ route('course-analytics') }}">{{ $dashboardMetrics['courses_in_progress'] }}<span class="sr-only"> courses in progress, view course analytics</span></a></dd></div>
                <div><dt>Assessments passed</dt><dd class="is-numeric"><a href="{{ route('course-analytics') }}">{{ $dashboardMetrics['assessments_passed'] }}<span class="sr-only"> assessments passed, view course analytics</span></a></dd></div>
            </dl>
        </header>

        <section class="atlas-callout" data-state="{{ $dashboardMetrics['needs_attention'] > 0 ? 'flagged' : 'clear' }}" aria-labelledby="attention-title">
            <div>
                <h2 id="attention-title" class="atlas-callout-label">Needs attention</h2>
                <p class="atlas-callout-figure">{{ $dashboardMetrics['needs_attention'] }} {{ Str::plural('student', $dashboardMetrics['needs_attention']) }} flagged</p>
                <p class="atlas-callout-copy">Review the queue for recorded signals and their supporting evidence. {{ $role === 'teacher' ? 'Attention covers your classroom scope. Roster filters do not narrow this count.' : 'Attention covers the platform scope. Roster filters do not narrow this count.' }}</p>
            </div>
            <a href="{{ route('needs-attention') }}" class="btn {{ $dashboardMetrics['needs_attention'] > 0 ? 'btn-primary' : 'btn-secondary' }}">Review attention queue →</a>
        </section>

        <section class="atlas-record-group" aria-labelledby="assessment-summary-title">
            <div class="atlas-record-group-head">
                <h2 id="assessment-summary-title" class="atlas-record-group-title">Assessment summary</h2>
                <p class="atlas-record-group-figure">{{ $dashboardSummary->count() }} {{ Str::plural('course', $dashboardSummary->count()) }}</p>
            </div>
            @forelse ($dashboardSummary as $row)
                <article class="atlas-record-entry atlas-record-entry--compact">
                    <div class="atlas-record-entry-row">
                        <div>
                            <h3 class="atlas-record-entry-title">{{ $row['course']->name }}</h3>
                            <p class="atlas-record-meta">
                                <span>Completed {{ $row['buckets']['completed'] }}</span>
                                <span>In progress {{ $row['buckets']['in_progress'] }}</span>
                                <span>Boss ready {{ $row['buckets']['assessment_ready'] }}</span>
                                <span>Not started {{ $row['buckets']['not_started'] }}</span>
                            </p>
                        </div>
                        <p class="atlas-record-entry-figure">Pass rate {{ $row['pass_rate'] !== null ? $row['pass_rate'].'%' : '—' }}</p>
                    </div>
                </article>
            @empty
                <p class="atlas-record-group-note">No active course with missions to summarize.</p>
            @endforelse
            <div class="atlas-records-toolbar">
                <p class="atlas-records-subheading">Course evidence</p>
                <a href="{{ route('course-analytics') }}" class="btn-ghost">Full analytics →</a>
            </div>
        </section>

        <section class="atlas-record-group" aria-labelledby="roster-title">
            <div class="atlas-record-group-head">
                <h2 id="roster-title" class="atlas-record-group-title">Student roster</h2>
                <p class="atlas-record-group-figure">{{ $students->total() }} {{ Str::plural('student', $students->total()) }} {{ $students->total() === 1 ? 'matches' : 'match' }} the current filters</p>
            </div>

            <form method="GET" action="{{ route('students') }}" class="atlas-index-filters" aria-label="Student roster filters">
                <div class="atlas-index-filter">
                    <label for="q">Search</label>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Username or name" class="terminal-input">
                </div>
                <div class="atlas-index-filter">
                    <label for="course">Current course</label>
                    <select id="course" name="course" class="terminal-input">
                        <option value="">All courses</option>
                        @foreach ($filterCourses as $course)
                            <option value="{{ $course->id }}" @selected(($filters['course'] ?? null) == $course->id)>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="atlas-index-filter">
                    <label for="status">Progress</label>
                    <select id="status" name="status" class="terminal-input">
                        <option value="">Any progress</option>
                        <option value="in_progress" @selected(($filters['status'] ?? null) === 'in_progress')>In progress</option>
                        <option value="ready" @selected(($filters['status'] ?? null) === 'ready')>Boss ready</option>
                        <option value="completed" @selected(($filters['status'] ?? null) === 'completed')>All cleared</option>
                    </select>
                </div>
                <div class="atlas-index-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply filters</button>
                    @if (! empty($filters['q']) || ! empty($filters['course']) || ! empty($filters['status']))
                        <a href="{{ route('students') }}" class="btn btn-ghost">Reset filters</a>
                    @endif
                </div>
            </form>

            @if ($errors->any())
                <div class="atlas-index-alerts">
                    <div class="panel border-warning/40 bg-warning-soft px-4 py-3 text-sm" role="alert">
                        <strong class="text-warning">Query rejected</strong>
                        <span class="text-fg-muted">{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            @if ($students->isEmpty())
                <section class="atlas-records-empty" aria-labelledby="empty-roster-title">
                    <h3 id="empty-roster-title" class="atlas-records-empty-title">No students match</h3>
                    <p class="atlas-records-empty-copy">No students match the current filters in your authorized scope.</p>
                    @if (! empty($filters['q']) || ! empty($filters['course']) || ! empty($filters['status']))
                        <a href="{{ route('students') }}" class="btn btn-ghost">Reset filters →</a>
                    @elseif ($role === 'teacher')
                        <a href="{{ route('classrooms') }}" class="btn btn-ghost">My classrooms →</a>
                    @endif
                </section>
            @else
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Current learning evidence</caption>
                        <thead>
                            <tr>
                                <th scope="col">Student</th>
                                <th scope="col">Course</th>
                                <th scope="col">Progress</th>
                                <th scope="col">Boss Challenge</th>
                                <th scope="col">Competency</th>
                                <th scope="col">Last activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $row)
                                @php
                                    $stateLabels = ['in_progress' => 'In progress', 'ready' => 'Boss ready', 'completed' => 'All cleared'];
                                    $stateTones = ['in_progress' => 'atlas-stamp--ink', 'ready' => 'atlas-stamp--vio', 'completed' => 'atlas-stamp--grn'];
                                    $counts = $row['competency'];
                                    $competencyParts = [];
                                    if ($counts['demonstrated'] > 0) {
                                        $competencyParts[] = 'Demonstrated '.$counts['demonstrated'];
                                    }
                                    if ($counts['practicing'] > 0) {
                                        $competencyParts[] = 'Practicing '.$counts['practicing'];
                                    }
                                    if ($counts['developing'] > 0) {
                                        $competencyParts[] = 'Developing '.$counts['developing'];
                                    }
                                    if ($counts['not_started'] > 0) {
                                        $competencyParts[] = 'Starting '.$counts['not_started'];
                                    }
                                    $competencyLabel = $competencyParts === [] ? '—' : implode(' · ', $competencyParts);
                                @endphp
                                <tr>
                                    <th scope="row" data-label="Student">
                                        <a href="{{ route('student-progress', ['student' => $row['id']]) }}">{{ $row['username'] }}</a>
                                        @if ($row['name'])
                                            <span class="atlas-register-note">{{ $row['name'] }}</span>
                                        @endif
                                    </th>
                                    <td data-label="Course">{{ $row['currentCourse']['name'] ?? '—' }}</td>
                                    <td data-label="Progress">
                                        @if ($row['progress'] !== null)
                                            <span class="atlas-register-figure">{{ $row['progress']['completed'] }}/{{ $row['progress']['total'] }} · {{ $row['progress']['percent'] }}%</span>
                                            <span class="atlas-stamp {{ $stateTones[$row['state']] ?? 'atlas-stamp--ink' }}">{{ $stateLabels[$row['state']] ?? $row['state'] }}</span>
                                        @else
                                            <span class="atlas-stamp atlas-stamp--grn">All cleared</span>
                                        @endif
                                    </td>
                                    <td data-label="Boss Challenge">
                                        @if ($row['assessment'] === 'READY')
                                            <span class="atlas-stamp atlas-stamp--vio">Ready</span>
                                        @elseif ($row['assessment'] === 'ALL CLEARED')
                                            <span class="atlas-stamp atlas-stamp--grn">Passed</span>
                                        @else
                                            <span class="atlas-stamp atlas-stamp--ink">Locked</span>
                                        @endif
                                    </td>
                                    <td data-label="Competency">{{ $competencyLabel }}</td>
                                    <td data-label="Last activity">
                                        @if ($row['lastActivity'] !== null)
                                            <span>{{ $row['lastActivity']['message'] }}</span>
                                            <span class="atlas-register-note">{{ $row['lastActivity']['at'] }}</span>
                                        @else
                                            <span class="atlas-register-note">No activity recorded</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($students->hasPages())
                    <nav class="atlas-records-pager" aria-label="Student roster pages">
                        <p class="atlas-records-pager-figure">Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }} · Page {{ $students->currentPage() }} of {{ $students->lastPage() }}</p>
                        <div class="atlas-records-pager-actions">
                            @if ($students->onFirstPage())
                                <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">← Previous</span>
                            @else
                                <a href="{{ $students->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Previous</a>
                            @endif
                            @if ($students->hasMorePages())
                                <a href="{{ $students->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                            @else
                                <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">Next →</span>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </section>

        <section class="atlas-record-group" aria-labelledby="activity-title">
            <div class="atlas-record-group-head">
                <h2 id="activity-title" class="atlas-record-group-title">Recent activity</h2>
                <p class="atlas-record-group-figure">Last 14 days · {{ $dashboardActivity->count() }} {{ Str::plural('event', $dashboardActivity->count()) }}</p>
            </div>
            @if ($dashboardActivity->isEmpty())
                <p class="atlas-record-group-note">No learning activity in the last 14 days.</p>
            @else
                <ol class="atlas-records-list">
                    @foreach ($dashboardActivity as $beat)
                        <li class="atlas-record-entry atlas-record-entry--compact">
                            <div class="atlas-record-entry-row">
                                <div>
                                    <p class="atlas-record-meta">{{ $beat['user']['username'] }}</p>
                                    <p class="atlas-record-entry-line">{{ $beat['label'] }}</p>
                                </div>
                                <time class="atlas-record-entry-figure" datetime="{{ $beat['at']->toIso8601String() }}">{{ $beat['at']->diffForHumans() }}</time>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
            <div class="atlas-records-toolbar">
                <p class="atlas-records-subheading">Learning activity</p>
                <a href="{{ route('activity') }}" class="btn-ghost">View all →</a>
            </div>
        </section>
    </div>
@endsection
