@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Course Progress')

@section('content')
    @php
        $labels = [
            'COMPLETED' => ['label' => 'Cleared', 'stamp' => 'atlas-stamp--grn'],
            'READY' => ['label' => 'Ready', 'stamp' => 'atlas-stamp--acc'],
            'IN PROGRESS' => ['label' => 'In progress', 'stamp' => 'atlas-stamp--vio'],
            'LOCKED' => ['label' => 'Locked', 'stamp' => 'atlas-stamp--ink'],
        ];
        $completedChallenges = $overview->sum(fn (array $row): int => $row['progress']['completed']);
        $totalChallenges = $overview->sum(fn (array $row): int => $row['progress']['total']);
        $clearedCourses = $overview->filter(fn (array $row): bool => $row['state'] === 'COMPLETED')->count();
    @endphp
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Course progress</span>
            <a href="{{ route('learning-path') }}">Learning path →</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Progress</span>
            <h1 class="atlas-records-title">Course progress</h1>
            <p class="atlas-records-lede">Challenge completion and the Boss Challenge milestone for every course in your path.</p>
            <dl class="log-facts atlas-records-facts">
                <div>
                    <dt>Courses</dt>
                    <dd class="is-numeric">{{ $overview->count() }}</dd>
                </div>
                <div>
                    <dt>Challenges complete</dt>
                    <dd class="is-numeric">{{ $completedChallenges }} / {{ $totalChallenges }}</dd>
                </div>
                <div>
                    <dt>Gates cleared</dt>
                    <dd class="is-numeric">{{ $clearedCourses }}</dd>
                </div>
            </dl>
        </header>

        @if ($overview->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="no-courses-title">
                <h2 id="no-courses-title" class="atlas-records-empty-title">No courses yet</h2>
                <p class="atlas-records-empty-copy">No courses are available in your learning path yet. They appear here once Command opens them.</p>
            </section>
        @else
            <ol class="atlas-records-list">
                @foreach ($overview as $row)
                    @php
                        $course = $row['course'];
                        $progress = $row['progress'];
                        $state = $labels[$row['state']] ?? ['label' => 'Locked', 'stamp' => 'atlas-stamp--ink'];
                    @endphp
                    <li class="atlas-record-entry" data-course-state="{{ strtolower(str_replace(' ', '-', $row['state'])) }}">
                        <div class="atlas-record-entry-head">
                            <span class="atlas-record-entry-label">Course {{ str_pad((string) $course->order_num, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="atlas-stamp {{ $state['stamp'] }}">@if ($stampIcon = \App\Support\StampIcon::for($state['label']))<x-icon :name="$stampIcon" size="12" />@endif{{ $state['label'] }}</span>
                        </div>
                        <h2 class="atlas-record-entry-title">{{ $course->name }}</h2>
                        @if ($course->description)
                            <p class="atlas-record-entry-copy">{{ $course->description }}</p>
                        @endif
                        <dl class="log-facts atlas-record-entry-facts">
                            <div>
                                <dt>Challenges</dt>
                                <dd class="is-numeric">{{ $progress['completed'] }}/{{ $progress['total'] }}</dd>
                            </div>
                            <div>
                                <dt>Complete</dt>
                                <dd class="is-numeric">{{ $progress['percent'] }}%</dd>
                            </div>
                        </dl>
                        @if ($progress['total'] > 0)
                            <div class="progress atlas-record-entry-bar" role="progressbar" aria-label="{{ $course->name }} challenge progress" aria-valuemin="0" aria-valuemax="{{ $progress['total'] }}" aria-valuenow="{{ $progress['completed'] }}" aria-valuetext="{{ $progress['completed'] }} of {{ $progress['total'] }} challenges complete">
                                <span style="width: {{ $progress['percent'] }}%"></span>
                            </div>
                        @endif
                        <p class="atlas-record-entry-note">
                            @if ($row['state'] === 'COMPLETED')
                                Boss Challenge cleared. Your course result is recorded.
                            @elseif ($row['state'] === 'READY')
                                Every challenge is complete. The Boss Challenge is open.
                            @elseif ($row['state'] === 'IN PROGRESS')
                                Keep working the challenges to reach the Boss Challenge.
                            @else
                                This course opens when the earlier course is cleared.
                            @endif
                        </p>
                        @if ($row['state'] === 'READY')
                            <div class="atlas-record-entry-actions">
                                <a href="{{ route('assessments') }}" class="btn btn-primary">View Boss Challenge →</a>
                            </div>
                        @elseif ($row['state'] === 'IN PROGRESS')
                            <div class="atlas-record-entry-actions">
                                <a href="{{ route('learning-path') }}" class="btn btn-secondary">Continue learning →</a>
                            </div>
                        @elseif ($row['state'] === 'COMPLETED')
                            <div class="atlas-record-entry-actions">
                                <a href="{{ route('assessments') }}" class="btn btn-secondary">Review result →</a>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
@endsection
