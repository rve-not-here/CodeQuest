@extends('layouts.app', ['role' => $role])

@section('title', 'Teacher Dashboard')

@section('content')
    <div class="atlas-teacher">
        <header class="teacher-context">
            <div>
                <h1>Teacher Dashboard</h1>
                <p>{{ $role === 'teacher' ? 'Monitor students and assigned courses in your active classrooms.' : 'Monitor student progress across all courses.' }}</p>
            </div>
            @if ($role === 'teacher')
                <a href="{{ route('classrooms') }}" class="btn-ghost">My classrooms →</a>
            @else
                <span class="teacher-state">SYSTEM-WIDE</span>
            @endif
        </header>

        <section class="teacher-scope" aria-labelledby="scope-title">
            <h2 id="scope-title">{{ $role === 'teacher' ? 'Classroom scope' : 'Platform scope' }}</h2>
            <dl>
                <div><dt>Total students</dt><dd>{{ $dashboardMetrics['total_students'] }}</dd></div>
                <div><dt>Active (14d)</dt><dd><a href="{{ route('activity') }}">{{ $dashboardMetrics['active_students'] }}<span class="sr-only"> active students, view learning activity</span></a></dd></div>
                <div><dt>Courses in progress</dt><dd><a href="{{ route('course-analytics') }}">{{ $dashboardMetrics['courses_in_progress'] }}<span class="sr-only"> courses in progress, view course analytics</span></a></dd></div>
                <div><dt>Assessments passed</dt><dd><a href="{{ route('course-analytics') }}">{{ $dashboardMetrics['assessments_passed'] }}<span class="sr-only"> assessments passed, view course analytics</span></a></dd></div>
            </dl>
        </section>

        <div class="teacher-overview">
            <section class="teacher-attention" aria-labelledby="attention-title">
                <h2 id="attention-title">Needs attention</h2>
                <p class="teacher-attention-count"><span>{{ $dashboardMetrics['needs_attention'] }}</span> {{ Str::plural('student', $dashboardMetrics['needs_attention']) }} flagged</p>
                <p>Review the complete list for recorded signals and learning evidence.</p>
                <a href="{{ route('needs-attention') }}" class="btn-primary">View Needs Attention →</a>
                <p class="teacher-scope-note">{{ $role === 'teacher' ? 'Attention covers your classroom scope. Roster filters do not narrow this count.' : 'Attention covers the platform scope. Roster filters do not narrow this count.' }}</p>
            </section>
            <section class="teacher-assessments" aria-labelledby="summary-title">
                <header class="teacher-section-heading">
                    <h2 id="summary-title">Assessment Summary</h2>
                    <a href="{{ route('course-analytics') }}" class="btn-ghost">Full analytics →</a>
                </header>
                @forelse ($dashboardSummary as $row)
                    <article class="teacher-course-evidence">
                        <div>
                            <h3>{{ $row['course']->name }}</h3>
                            <p>PASS RATE {{ $row['pass_rate'] !== null ? $row['pass_rate'].'%' : '—' }}</p>
                        </div>
                        <dl>
                            <div><dt>Complete</dt><dd>{{ $row['buckets']['completed'] }}</dd></div>
                            <div><dt>In progress</dt><dd>{{ $row['buckets']['in_progress'] }}</dd></div>
                            <div><dt>Boss ready</dt><dd>{{ $row['buckets']['assessment_ready'] }}</dd></div>
                            <div><dt>Not started</dt><dd>{{ $row['buckets']['not_started'] }}</dd></div>
                        </dl>
                    </article>
                @empty
                    <p class="teacher-empty">No active course with missions to summarize.</p>
                @endforelse
            </section>
        </div>

        <section class="teacher-roster" aria-labelledby="roster-title">
            <header class="teacher-section-heading">
                <h2 id="roster-title">Student Roster</h2>
                <p class="result-count">{{ $students->total() }} {{ Str::plural('student', $students->total()) }} {{ $students->total() === 1 ? 'matches' : 'match' }} the current filters.</p>
            </header>
            <form method="GET" action="{{ route('students') }}" class="teacher-filters">
                <div class="teacher-filter-fields">
                    <div>
                        <label for="q">Search</label>
                        <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Username or name" class="terminal-input">
                    </div>
                    <div>
                        <label for="course">Current course</label>
                        <select id="course" name="course" class="terminal-input">
                            <option value="">All courses</option>
                            @foreach ($filterCourses as $course)
                                <option value="{{ $course->id }}" @selected(($filters['course'] ?? null) == $course->id)>{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status">Progress</label>
                        <select id="status" name="status" class="terminal-input">
                            <option value="">ANY</option>
                            <option value="in_progress" @selected(($filters['status'] ?? null) === 'in_progress')>IN PROGRESS</option>
                            <option value="ready" @selected(($filters['status'] ?? null) === 'ready')>READY</option>
                            <option value="completed" @selected(($filters['status'] ?? null) === 'completed')>ALL CLEARED</option>
                        </select>
                    </div>
                    <div class="teacher-filter-actions">
                        <button type="submit" class="btn-ghost">FILTER →</button>
                        @if (! empty($filters['q']) || ! empty($filters['course']) || ! empty($filters['status']))
                            <a href="{{ route('students') }}" class="btn-ghost">CLEAR</a>
                        @endif
                    </div>
                </div>
                @if ($errors->any())
                    <x-status-message type="error" title="QUERY REJECTED" class="mt-3">
                        @foreach ($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </x-status-message>
                @endif
            </form>

            @if ($students->isEmpty())
                <section class="teacher-empty" aria-labelledby="empty-roster-title">
                    <h3 id="empty-roster-title">NO TRAINEES</h3>
                    <p>No students match the current filters in your authorized scope.</p>
                    @if (! empty($filters['q']) || ! empty($filters['course']) || ! empty($filters['status']))
                        <a href="{{ route('students') }}" class="btn-ghost">Clear filters →</a>
                    @elseif ($role === 'teacher')
                        <a href="{{ route('classrooms') }}" class="btn-ghost">View My Classrooms →</a>
                    @endif
                </section>
            @else
                <table class="table-stack" role="table">
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
                                $stateLabel = strtoupper(str_replace('_', ' ', $row['state']));
                                $counts = $row['competency'];
                                $competencyParts = [];
                                if ($counts['demonstrated'] > 0) {
                                    $competencyParts[] = 'DEMONSTRATED '.$counts['demonstrated'];
                                }
                                if ($counts['practicing'] > 0) {
                                    $competencyParts[] = 'PRACTICING '.$counts['practicing'];
                                }
                                if ($counts['developing'] > 0) {
                                    $competencyParts[] = 'DEVELOPING '.$counts['developing'];
                                }
                                if ($counts['not_started'] > 0) {
                                    $competencyParts[] = 'STARTING '.$counts['not_started'];
                                }
                                $competencyLabel = $competencyParts === [] ? '—' : implode(' · ', $competencyParts);
                            @endphp
                            <tr>
                                <th scope="row" data-label="Student">
                                    <a href="{{ route('student-progress', ['student' => $row['id']]) }}">{{ $row['username'] }}</a>
                                    @if ($row['name'])
                                        <span class="teacher-student-name">{{ $row['name'] }}</span>
                                    @endif
                                </th>
                                <td data-label="Course">{{ $row['currentCourse']['name'] ?? '—' }}</td>
                                <td data-label="Progress">
                                    @if ($row['progress'] !== null)
                                        <span class="teacher-state" data-state="{{ $row['state'] }}">{{ $stateLabel }} {{ $row['progress']['completed'] }}/{{ $row['progress']['total'] }} · {{ $row['progress']['percent'] }}%</span>
                                    @else
                                        <span class="teacher-state" data-state="completed">ALL CLEARED</span>
                                    @endif
                                </td>
                                <td data-label="Boss Challenge"><span class="teacher-state" data-state="{{ strtolower(str_replace(' ', '-', $row['assessment'])) }}">{{ $row['assessment'] }}</span></td>
                                <td data-label="Competency">{{ $competencyLabel }}</td>
                                <td data-label="Last activity">
                                    @if ($row['lastActivity'] !== null)
                                        <span>{{ $row['lastActivity']['message'] }}</span>
                                        <span class="teacher-activity-time">{{ $row['lastActivity']['at'] }}</span>
                                    @else
                                        <span class="text-static">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <nav class="teacher-pagination" aria-label="Student roster pagination">
                    <p>Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }} matching students</p>
                    @if ($students->hasPages())
                        <div>
                            <span>SHOWING PAGE {{ $students->currentPage() }} OF {{ $students->lastPage() }}</span>
                            @if ($students->onFirstPage())
                                <span class="teacher-page-disabled">◀ PREV</span>
                            @else
                                <a href="{{ $students->previousPageUrl() }}" class="btn-ghost">◀ PREV</a>
                            @endif
                            @if ($students->hasMorePages())
                                <a href="{{ $students->nextPageUrl() }}" class="btn-ghost">NEXT ▶</a>
                            @else
                                <span class="teacher-page-disabled">NEXT ▶</span>
                            @endif
                        </div>
                    @endif
                </nav>
            @endif
        </section>

        <section class="teacher-activity" aria-labelledby="activity-title">
            <header class="teacher-section-heading">
                <div><h2 id="activity-title">Recent Activity</h2><p>Recorded learning activity in the last 14 days.</p></div>
                <a href="{{ route('activity') }}" class="btn-ghost">VIEW ALL →</a>
            </header>
            <ol>
                @forelse ($dashboardActivity as $beat)
                    <li>
                        <span class="teacher-activity-student">{{ $beat['user']['username'] }}</span>
                        <span>{{ $beat['label'] }}</span>
                        <time datetime="{{ $beat['at']->toIso8601String() }}">{{ $beat['at']->diffForHumans() }}</time>
                    </li>
                @empty
                    <li class="teacher-empty">No learning activity in the last 14 days.</li>
                @endforelse
            </ol>
        </section>
    </div>
@endsection
