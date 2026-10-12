@extends('layouts.app', ['role' => $role])

@section('title', 'Course Analytics')

@section('content')
    <div class="teacher-operations">
        <header class="teacher-context">
            <div>
                <h1>Course Analytics</h1>
                <p>{{ $role === 'teacher' ? 'Classroom scope' : 'SYSTEM-WIDE' }} · Course evidence derived from recorded learning and assessments.</p>
            </div>
            <a href="{{ route('students') }}" class="btn-ghost">Student roster →</a>
        </header>
        @forelse ($courses as $course)
            <section class="teacher-record-section" aria-labelledby="analytics-course-{{ $course['course']->id }}">
                <header class="teacher-section-heading">
                    <div><h2 id="analytics-course-{{ $course['course']->id }}">{{ strtoupper($course['course']->name) }}</h2><p class="teacher-state">COURSE {{ $course['course']->order_num }} · {{ strtoupper($course['course']->type) }} · STUDENTS {{ $course['engaged'] }}/{{ $course['fleet'] }}</p></div>
                    @if ($role === 'teacher')
                        <a href="{{ route('reports.teacher-course', $course['course']) }}" class="btn-ghost">VIEW COURSE REPORT →</a>
                    @endif
                </header>
                <dl class="teacher-evidence-strip" aria-label="Course lifecycle">
                    <div><dt>Completed</dt><dd>{{ $course['buckets']['completed'] }}</dd></div>
                    <div><dt>In progress</dt><dd>{{ $course['buckets']['in_progress'] }}</dd></div>
                    <div><dt>Boss ready</dt><dd>{{ $course['buckets']['assessment_ready'] }}</dd></div>
                    <div><dt>Not started</dt><dd>{{ $course['buckets']['not_started'] }}</dd></div>
                </dl>
                <div class="teacher-analytics-evidence">
                    <dl class="teacher-evidence-measures">
                        <div><dt>Average challenge completion</dt><dd>{{ $course['avg_completion'] !== null ? $course['avg_completion'].'%' : 'No evidence' }}</dd></div>
                        <div><dt>Boss Challenge pass rate</dt><dd>{{ $course['pass_rate'] !== null ? $course['pass_rate'].'%' : 'No evidence' }}</dd></div>
                    </dl>
                    @if ($course['engaged'] > 0)
                        @php($bands = \App\Services\CourseAnalyticsService::DISTRIBUTION_BANDS)
                        <div>
                            <p class="teacher-note">{{ $course['engaged'] }} engaged students, grouped by completed challenges.</p>
                            <table class="table-stack teacher-evidence-table" role="table">
                                <caption>Completion distribution</caption>
                                <thead><tr><th scope="col">Challenge completion</th><th scope="col">Students</th></tr></thead>
                                <tbody>
                                    @foreach ($course['distribution'] as $index => $count)
                                        <tr><th scope="row" data-label="Challenge completion">{{ $bands[$index] }}%</th><td data-label="Students">{{ $count }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        @empty
            <section class="teacher-empty"><h2>No courses</h2><p>No active course with missions is available to summarize.</p></section>
        @endforelse
    </div>
@endsection
