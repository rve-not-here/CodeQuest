@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Course Progress')

@section('content')
    <div class="mx-auto w-full max-w-[1080px]">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
            <div>
                <p class="eyebrow">Learning record</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">Course Progress</h1>
                <p class="mt-2 max-w-[64ch] text-sm leading-6 text-fg-muted">See mission completion and the Boss Challenge milestone for each course.</p>
            </div>
            <a href="{{ route('learning-path') }}" class="btn btn-secondary btn-sm">View Learning Path →</a>
        </header>

        @if ($overview->isEmpty())
            <section class="panel mt-6 px-6 py-10 text-center" aria-labelledby="no-courses-title">
                <h2 id="no-courses-title" class="text-lg font-semibold">NO COURSES</h2>
                <p class="mt-2 text-sm text-fg-muted">No courses are available in your learning path yet.</p>
            </section>
        @else
            <ol class="mt-6 grid grid-cols-1 gap-4">
                @foreach ($overview as $row)
                    @php
                        $course = $row['course'];
                        $progress = $row['progress'];
                        $state = $row['state'];
                    @endphp
                    <li class="panel min-w-0 overflow-hidden" data-course-state="{{ strtolower(str_replace(' ', '-', $state)) }}">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4 md:px-6">
                            <div class="min-w-0">
                                <p class="eyebrow">Course {{ str_pad((string) $course->order_num, 2, '0', STR_PAD_LEFT) }}</p>
                                <h2 class="mt-1 text-lg font-semibold text-balance">{{ $course->name }}</h2>
                                @if ($course->description)
                                    <p class="mt-1 max-w-[68ch] text-sm leading-6 text-fg-muted">{{ $course->description }}</p>
                                @endif
                            </div>
                            <span class="badge {{ $state === 'COMPLETED' || $state === 'READY' ? 'badge-accent' : ($state === 'IN PROGRESS' ? 'badge-warning' : 'badge-neutral') }}">{{ $state }}</span>
                        </div>
                        <div class="grid grid-cols-1 gap-5 px-5 py-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-end md:px-6">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                                    <p class="text-fg-muted"><span class="font-mono text-fg">{{ $progress['completed'] }}/{{ $progress['total'] }}</span> missions complete</p>
                                    <span class="font-mono text-fg">{{ $progress['percent'] }}%</span>
                                </div>
                                @if ($progress['total'] > 0)
                                    <div class="progress mt-3" role="progressbar" aria-label="{{ $course->name }} mission progress" aria-valuemin="0" aria-valuemax="{{ $progress['total'] }}" aria-valuenow="{{ $progress['completed'] }}" aria-valuetext="{{ $progress['completed'] }} of {{ $progress['total'] }} missions complete">
                                        <span style="width: {{ $progress['percent'] }}%"></span>
                                    </div>
                                @endif
                                <p class="mt-3 text-xs leading-5 text-fg-subtle">
                                    @if ($state === 'COMPLETED')
                                        Boss Challenge cleared. Your course result is recorded.
                                    @elseif ($state === 'READY')
                                        All missions complete. The Boss Challenge is ready.
                                    @elseif ($state === 'IN PROGRESS')
                                        Continue your missions to reach the Boss Challenge.
                                    @else
                                        This course opens when the earlier course is cleared and the course is active.
                                    @endif
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2 md:justify-end">
                                @if ($state === 'READY')
                                    <a href="{{ route('assessments') }}" class="btn btn-primary">View Boss Challenge →</a>
                                @elseif ($state === 'IN PROGRESS')
                                    <a href="{{ route('learning-path') }}" class="btn btn-primary">Continue Learning →</a>
                                @elseif ($state === 'COMPLETED')
                                    <a href="{{ route('assessments') }}" class="btn btn-secondary">Review result →</a>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
@endsection
