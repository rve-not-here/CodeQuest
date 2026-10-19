@extends('layouts.app', ['role' => $role])

@section('title', 'Course analytics')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Course analytics · {{ $role === 'teacher' ? 'Classroom scope' : 'Platform scope' }}</span>
            <a href="{{ route('students') }}">Students →</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Course evidence</span>
            <h1 class="atlas-records-title">Course analytics</h1>
            <p class="atlas-records-lede">Course evidence derived from recorded learning and assessments. Every figure counts the same students the course pages do.</p>
        </header>

        @if ($courses->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="empty-analytics-title">
                <h2 id="empty-analytics-title" class="atlas-records-empty-title">No courses</h2>
                <p class="atlas-records-empty-copy">No active course with missions is available to summarize.</p>
                <a href="{{ route('students') }}" class="btn btn-ghost">Student roster →</a>
            </section>
        @else
            <section class="atlas-record-group" aria-labelledby="course-evidence-title">
                <div class="atlas-record-group-head">
                    <h2 id="course-evidence-title" class="atlas-record-group-title">Course evidence</h2>
                    <p class="atlas-record-group-figure">{{ $courses->count() }} {{ Str::plural('course', $courses->count()) }}</p>
                </div>
                <ol class="atlas-records-list">
                    @foreach ($courses as $course)
                        @php
                            $bands = \App\Services\CourseAnalyticsService::DISTRIBUTION_BANDS;
                            $peak = max($course['distribution'] ?: [0]);
                        @endphp
                        <li class="atlas-record-entry">
                            <div class="atlas-record-entry-head">
                                <h3 id="analytics-course-{{ $course['course']->id }}" class="atlas-record-entry-title">{{ $course['course']->name }}</h3>
                                <p class="atlas-record-entry-figure">{{ $course['engaged'] }}/{{ $course['fleet'] }} students engaged</p>
                            </div>
                            <p class="atlas-record-meta">
                                <span>Course {{ $course['course']->order_num }}</span>
                                <span>{{ strtoupper($course['course']->type) }}</span>
                            </p>
                            <dl class="log-facts atlas-record-entry-facts" aria-label="Course lifecycle">
                                <div><dt>Completed</dt><dd class="is-numeric">{{ $course['buckets']['completed'] }}</dd></div>
                                <div><dt>In progress</dt><dd class="is-numeric">{{ $course['buckets']['in_progress'] }}</dd></div>
                                <div><dt>Boss ready</dt><dd class="is-numeric">{{ $course['buckets']['assessment_ready'] }}</dd></div>
                                <div><dt>Not started</dt><dd class="is-numeric">{{ $course['buckets']['not_started'] }}</dd></div>
                            </dl>
                            <dl class="log-facts atlas-record-entry-facts" aria-label="Course measures">
                                <div><dt>Average challenge completion</dt><dd class="is-numeric">{{ $course['avg_completion'] !== null ? $course['avg_completion'].'%' : 'No evidence' }}</dd></div>
                                <div><dt>Boss Challenge pass rate</dt><dd class="is-numeric">{{ $course['pass_rate'] !== null ? $course['pass_rate'].'%' : 'No evidence' }}</dd></div>
                            </dl>
                            @if ($course['engaged'] > 0)
                                <div class="atlas-distribution">
                                    <p class="atlas-record-entry-label">Completion distribution</p>
                                    <ul class="atlas-distribution-list" aria-label="Completion distribution">
                                        @foreach ($course['distribution'] as $index => $count)
                                            <li class="atlas-distribution-row">
                                                <span class="atlas-distribution-band">{{ $bands[$index] }}%</span>
                                                <span class="atlas-distribution-bar"><span style="width: {{ $peak > 0 ? round($count / $peak * 100) : 0 }}%"></span></span>
                                                <span class="atlas-distribution-count">{{ $count }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <p class="atlas-record-entry-note">{{ $course['engaged'] }} {{ Str::plural('engaged student', $course['engaged']) }}, grouped by completed challenges.</p>
                                </div>
                            @endif
                            @if ($role === 'teacher')
                                <div class="atlas-record-entry-actions">
                                    <a href="{{ route('reports.teacher-course', $course['course']) }}" class="btn btn-secondary btn-sm">Course report →</a>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
                <p class="atlas-record-group-note">Engaged students are the students visible in this scope who have at least one recorded challenge event in the course.</p>
            </section>
        @endif
    </div>
@endsection
