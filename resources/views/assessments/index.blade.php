@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Boss Challenges')

@php
    $labels = [
        'none' => 'Not authored',
        'sealed' => 'Sealed',
        'ready' => 'Ready',
        'in-progress' => 'In progress',
        'submitted' => 'Awaiting verdict',
        'failed' => 'Failed',
        'passed' => 'Cleared',
        'failed-retry' => 'Cleared',
    ];
    $stamps = [
        'none' => 'atlas-stamp--ink',
        'sealed' => 'atlas-stamp--ink',
        'ready' => 'atlas-stamp--acc',
        'in-progress' => 'atlas-stamp--vio',
        'submitted' => 'atlas-stamp--vio',
        'failed' => 'atlas-stamp--amb',
        'passed' => 'atlas-stamp--grn',
        'failed-retry' => 'atlas-stamp--grn',
    ];
    $notes = [
        'ready' => 'Every challenge in this course is complete. The gate is open.',
        'in-progress' => 'An attempt is open. Continue where you left off.',
        'submitted' => 'Your submission is recorded and awaiting its verdict.',
        'failed' => 'The last attempt did not clear. A retry opens a fresh attempt.',
    ];
    $cleared = $rows->filter(fn (array $row): bool => in_array($row['state'], ['passed', 'failed-retry'], true))->count();
@endphp

@section('content')
    <div class="atlas-assessments">
        <div class="log-running">
            <span>Boss challenges · The final gate in each course</span>
            <a href="{{ route('learning-path') }}">Learning path →</a>
        </div>

        <header class="atlas-reading-heading">
            <div class="atlas-lesson-entry">
                <span class="atlas-lesson-entry-label">Assessment record</span>
            </div>
            <h1 class="atlas-reading-title">Boss Challenges</h1>
            <p class="atlas-check-lede">Complete the challenges in each course to open its final Boss Challenge. Your challenge status comes from your recorded work.</p>
            <dl class="log-facts atlas-assessments-facts">
                <div>
                    <dt>Courses</dt>
                    <dd class="is-numeric">{{ $rows->count() }}</dd>
                </div>
                <div>
                    <dt>Gates cleared</dt>
                    <dd class="is-numeric">{{ $cleared }}</dd>
                </div>
                <div>
                    <dt>XP</dt>
                    <dd class="is-numeric">{{ $totalXp }}</dd>
                </div>
            </dl>
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
            <section class="atlas-assessment-empty" aria-labelledby="empty-challenges-title">
                <h2 id="empty-challenges-title">No courses available yet</h2>
                <p>No active courses are assigned to your path.</p>
                <a href="{{ route('learning-path') }}" class="btn btn-secondary">View learning path</a>
            </section>
        @else
            <ol class="atlas-assessment-list">
                @foreach ($rows as $row)
                    @php
                        $course = $row['course'];
                        $assessment = $row['assessment'];
                        $state = $row['state'];
                        $completedMissions = $row['missionProgress']['completed'];
                        $totalMissions = $row['missionProgress']['total'];
                        $note = match ($state) {
                            'sealed' => $row['lockedReason'],
                            'none' => 'The Boss Challenge has not been published for this course.',
                            'failed-retry' => 'Last retry failed — your pass stands.',
                            'passed' => $row['unlocked']
                                ? 'Course cleared. Your result remains available to review.'
                                : 'Historical pass retained. '.$row['lockedReason'],
                            default => $notes[$state] ?? null,
                        };
                    @endphp
                    <li class="atlas-assessment-entry" data-assessment-state="{{ $state }}">
                        <div class="atlas-assessment-head">
                            <div class="atlas-assessment-heading">
                                <p class="atlas-assessment-order">Course {{ str_pad((string) $course->order_num, 2, '0', STR_PAD_LEFT) }}</p>
                                <h2>{{ $course->name }}</h2>
                                <p class="atlas-assessment-challenge">{{ $assessment?->title ?? 'No Boss Challenge authored for this course.' }}</p>
                            </div>
                            <span class="atlas-stamp {{ $stamps[$state] }}">@if ($stampIcon = \App\Support\StampIcon::for($labels[$state]))<x-icon :name="$stampIcon" size="12" />@endif{{ $labels[$state] }}</span>
                        </div>

                        <div class="atlas-assessment-body">
                            <div class="atlas-assessment-progress">
                                <dl class="log-facts atlas-assessment-facts">
                                    <div>
                                        <dt>Challenges</dt>
                                        <dd class="is-numeric">{{ $completedMissions }} / {{ $totalMissions }}</dd>
                                    </div>
                                    @if ($assessment)
                                        <div>
                                            <dt>Pass</dt>
                                            <dd class="is-numeric">{{ $assessment->passing_score }}%</dd>
                                        </div>
                                    @endif
                                </dl>
                                @if ($totalMissions > 0)
                                    <div class="progress atlas-assessment-bar" role="progressbar" aria-label="{{ $course->name }} challenge progress" aria-valuemin="0" aria-valuemax="{{ $totalMissions }}" aria-valuenow="{{ $completedMissions }}" aria-valuetext="{{ $completedMissions }} of {{ $totalMissions }} challenges complete">
                                        <span style="width: {{ min(100, round($completedMissions / $totalMissions * 100)) }}%"></span>
                                    </div>
                                @endif
                                @if ($note)
                                    <p class="atlas-assessment-note">{{ $note }}</p>
                                @endif
                            </div>

                            <div class="atlas-assessment-actions">
                                @if (! $row['unlocked'])
                                    <a href="{{ route('learning-path') }}" class="btn btn-secondary">View learning path <x-icon name="chevron-right" size="16" /></a>
                                @else
                                    @if ($state === 'ready')
                                        <form method="POST" action="{{ route('assessment.start', $assessment) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-primary">Start attempt <x-icon name="chevron-right" size="16" /></button>
                                        </form>
                                        <a href="{{ route('assessment.show', $assessment) }}" class="btn btn-ghost">View briefing</a>
                                    @elseif ($state === 'in-progress')
                                        <a href="{{ route('assessment.show', $assessment) }}" class="btn btn-primary">Continue challenge <x-icon name="chevron-right" size="16" /></a>
                                    @elseif ($state === 'submitted')
                                        <a href="{{ route('assessment.show', $assessment) }}" class="btn btn-secondary">View status</a>
                                    @elseif ($state === 'failed')
                                        <form method="POST" action="{{ route('assessment.retry', $assessment) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-primary">Retry challenge</button>
                                        </form>
                                        <a href="{{ route('assessment.show', $assessment) }}" class="btn btn-ghost">Review</a>
                                    @elseif (in_array($state, ['passed', 'failed-retry'], true))
                                        <a href="{{ route('assessment.show', $assessment) }}" class="btn btn-secondary">Open challenge</a>
                                    @elseif ($state === 'sealed')
                                        <a href="{{ route('missions') }}" class="btn btn-secondary">View challenges <x-icon name="chevron-right" size="16" /></a>
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
