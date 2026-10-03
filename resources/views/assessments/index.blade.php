@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Boss Challenges')

@php
    $labels = [
        'none' => 'NO CHALLENGE',
        'sealed' => 'SEALED',
        'ready' => 'READY',
        'in-progress' => 'IN PROGRESS',
        'submitted' => 'AWAITING VERDICT',
        'failed' => 'FAILED',
        'passed' => 'CLEARED',
        'failed-retry' => 'CLEARED',
    ];
    $badges = [
        'none' => 'badge-neutral',
        'sealed' => 'badge-neutral',
        'ready' => 'badge-accent',
        'in-progress' => 'badge-accent',
        'submitted' => 'badge-warning',
        'failed' => 'badge-warning',
        'passed' => 'badge-accent',
        'failed-retry' => 'badge-accent',
    ];
@endphp

@section('content')
    <div class="mx-auto w-full max-w-[1080px]">
        <header class="border-b border-line pb-6">
            <p class="eyebrow">Course milestones</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">Boss Challenges</h1>
            <p class="mt-2 max-w-[64ch] text-sm leading-6 text-fg-muted">Complete the challenges in each course to open its final Boss Challenge. Your challenge status comes from your recorded work.</p>
            <a href="{{ route('learning-path') }}" class="btn btn-ghost btn-sm mt-4">← View Learning Path</a>
        </header>

        @if (session('assessment_locked'))
            <div class="panel mt-5 border-warning/40 bg-warning-soft px-4 py-3 text-sm" role="status">
                <strong class="text-warning">{{ session('assessment_locked')['title'] }}.</strong>
                <span class="text-fg-muted">{{ session('assessment_locked')['message'] }}</span>
            </div>
        @endif
        @if (session('assessment_error'))
            <div class="panel mt-5 border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                <strong class="text-danger">{{ session('assessment_error')['title'] }}.</strong>
                <span class="text-fg-muted">{{ session('assessment_error')['message'] }}</span>
            </div>
        @endif

        @if ($rows->isEmpty())
            <section class="panel mt-6 px-6 py-10 text-center" aria-labelledby="empty-challenges-title">
                <h2 id="empty-challenges-title" class="text-lg font-semibold">No courses available yet</h2>
                <p class="mt-2 text-sm text-fg-muted">No active courses are assigned to your path.</p>
                <a href="{{ route('learning-path') }}" class="btn btn-secondary btn-sm mt-5">View Learning Path</a>
            </section>
        @else
            <ol class="mt-6 grid grid-cols-1 gap-4">
                @foreach ($rows as $row)
                    @php
                        $course = $row['course'];
                        $assessment = $row['assessment'];
                        $state = $row['state'];
                        $completedMissions = $row['missionProgress']['completed'];
                        $totalMissions = $row['missionProgress']['total'];
                    @endphp
                    <li class="panel min-w-0 overflow-hidden" data-assessment-state="{{ $state }}">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4 md:px-6">
                            <div class="min-w-0">
                                <p class="eyebrow">Course {{ str_pad((string) $course->order_num, 2, '0', STR_PAD_LEFT) }}</p>
                                <h2 class="mt-1 text-lg font-semibold text-balance">{{ $course->name }}</h2>
                                <p class="mt-1 text-sm text-fg-muted">{{ $assessment?->title ?? 'No Boss Challenge authored for this course.' }}</p>
                            </div>
                            <span class="badge {{ $badges[$state] }}">{{ $labels[$state] }}</span>
                        </div>

                        <div class="grid grid-cols-1 gap-5 px-5 py-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-end md:px-6">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-fg-subtle">
                                    <span>{{ $completedMissions }} / {{ $totalMissions }} challenges complete</span>
                                    @if ($assessment)
                                        <span>Pass at {{ $assessment->passing_score }}%</span>
                                    @endif
                                </div>
                                @if ($totalMissions > 0)
                                    <div class="progress mt-3 max-w-sm" role="progressbar" aria-label="{{ $course->name }} challenge progress" aria-valuemin="0" aria-valuemax="{{ $totalMissions }}" aria-valuenow="{{ $completedMissions }}" aria-valuetext="{{ $completedMissions }} of {{ $totalMissions }} challenges complete">
                                        <span style="width: {{ min(100, round($completedMissions / $totalMissions * 100)) }}%"></span>
                                    </div>
                                @endif
                                @if ($state === 'sealed')
                                    <p class="mt-3 text-sm text-fg-muted">{{ $row['lockedReason'] }}</p>
                                @elseif ($state === 'none')
                                    <p class="mt-3 text-sm text-fg-muted">The Boss Challenge has not been published for this course.</p>
                                @elseif ($state === 'failed-retry')
                                    <p class="mt-3 text-sm text-fg-muted">Last retry failed — your pass stands.</p>
                                @elseif ($state === 'passed')
                                    <p class="mt-3 text-sm text-fg-muted">{{ $row['unlocked'] ? 'Course cleared. Your result remains available to review.' : 'Historical pass retained. '.$row['lockedReason'] }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2 md:justify-end">
                                @if (! $row['unlocked'])
                                    <a href="{{ route('learning-path') }}" class="btn btn-secondary">View Learning Path →</a>
                                @else
                                @if ($state === 'ready')
                                    <form method="POST" action="{{ route('assessment.start', $assessment) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">INITIATE CHALLENGE</button>
                                    </form>
                                    <a href="{{ route('assessment.show', $assessment) }}" class="btn btn-ghost">VIEW BRIEFING</a>
                                @elseif ($state === 'in-progress')
                                    <a href="{{ route('assessment.show', $assessment) }}" class="btn btn-primary">CONTINUE CHALLENGE</a>
                                @elseif ($state === 'submitted')
                                    <a href="{{ route('assessment.show', $assessment) }}" class="btn btn-secondary">VIEW STATUS</a>
                                @elseif ($state === 'failed')
                                    <form method="POST" action="{{ route('assessment.retry', $assessment) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">RETRY CHALLENGE</button>
                                    </form>
                                    <a href="{{ route('assessment.show', $assessment) }}" class="btn btn-ghost">REVIEW</a>
                                @elseif (in_array($state, ['passed', 'failed-retry'], true))
                                    <a href="{{ route('assessment.show', $assessment) }}" class="btn btn-secondary">OPEN CHALLENGE</a>
                                @elseif ($state === 'sealed')
                                    <a href="{{ route('missions') }}" class="btn btn-secondary">View challenges →</a>
                                @endif
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
@endsection
