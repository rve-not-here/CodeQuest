@extends('layouts.app', ['role' => $role])

@php
    $displayName = auth()->user()?->username ?? 'OPERATOR';
    $firstName = strtok($displayName, ' ') ?: $displayName;
    $competencyState = $competencySummary === null
        ? 'NO EVIDENCE'
        : strtoupper(str_replace('_', ' ', $competencySummary['state']));
    $competencyTone = match ($competencySummary['state'] ?? null) {
        'demonstrated' => 'phosphor',
        'practicing' => 'amber',
        'developing' => 'cyan',
        default => 'dim',
    };
@endphp

@section('title', 'Dashboard')

@section('content')
    <x-page-header
        title="Mission Control"
        subtitle="Welcome back, {{ $firstName }}. Your next learning directive is ready."
    />

    <section class="dashboard-command" aria-labelledby="current-operation-title">
        <div class="dashboard-command-grid">
            <div class="min-w-0">
                <p class="terminal-kicker text-phosphor">
                    CURRENT OPERATION // {{ $course === null ? 'ALL CLEAR' : 'ACTIVE' }}
                </p>

                @if ($course === null)
                    <h2 id="current-operation-title" class="dashboard-command-title">Course complete</h2>
                    <p class="dashboard-command-copy">
                        You have cleared every active course. Awaiting new directives from Command.
                    </p>
                @elseif ($resume === null)
                    <p class="mt-4 text-sm uppercase tracking-[0.12em] text-static">{{ $course->name }}</p>
                    <h2 id="current-operation-title" class="dashboard-command-title">No pending challenge</h2>
                    <p class="dashboard-command-copy">Review the Learning Path for the next available operation.</p>
                @elseif ($resume['type'] === 'mission')
                    <p class="mt-4 text-sm uppercase tracking-[0.12em] text-static">{{ $course->name }}</p>
                    <h2 id="current-operation-title" class="dashboard-command-title">{{ $resume['mission']->title }}</h2>
                    <p class="dashboard-command-copy">
                        @if (! empty($resume['section']))
                            {{ $resume['section']->title }} //
                        @endif
                        {{ $resume['mission']->difficulty }} // +{{ $resume['mission']->points }} XP
                    </p>
                @else
                    <p class="mt-4 text-sm uppercase tracking-[0.12em] text-amber">Boss Challenge ready</p>
                    <h2 id="current-operation-title" class="dashboard-command-title">All challenges complete</h2>
                    <p class="dashboard-command-copy">Return to the Learning Path to inspect the course milestone.</p>
                @endif

                @if ($course !== null && $courseProgress !== null)
                    <div class="mt-6 max-w-2xl">
                        <x-progress-bar
                            label="{{ $course->name }}"
                            :total="$courseProgress['total']"
                            :current="$courseProgress['completed']"
                        />
                        <p class="mt-2 text-xs uppercase tracking-[0.1em] text-static">
                            {{ $courseProgress['completed'] }} of {{ $courseProgress['total'] }} challenges validated
                        </p>
                    </div>
                @endif
            </div>

            <div class="dashboard-command-action">
                @if ($resume !== null && $resume['type'] === 'mission')
                    <a href="{{ route($resumeHasDraft ? 'mission.challenge' : 'mission.show', $resume['mission']) }}" class="btn-primary btn-command">
                        {{ $resumeHasDraft ? 'Resume Challenge →' : 'Continue Learning →' }}
                    </a>
                    <p>{{ $resumeHasDraft ? 'Saved work detected.' : 'Open the current lesson.' }}</p>
                @elseif ($resume !== null && $resume['type'] === 'course')
                    <a href="{{ route('learning-path') }}" class="btn-primary btn-command">Continue Learning →</a>
                    <p>Boss milestone available.</p>
                @else
                    <a href="{{ route('learning-path') }}" class="btn-primary btn-command">View Learning Path →</a>
                    <p>Inspect completed operations.</p>
                @endif
            </div>
        </div>
    </section>

    <section class="dashboard-status-grid" aria-label="Learning status">
        <a href="{{ route('xp-ledger') }}" class="dashboard-status-cell">
            <span class="dashboard-status-label">XP BALANCE</span>
            <strong>{{ $totalXp }}</strong>
            <span>Open ledger →</span>
        </a>
        <a href="{{ route('competency') }}" class="dashboard-status-cell">
            <span class="dashboard-status-label">COMPETENCY</span>
            <strong class="text-base {{ $competencyTone === 'phosphor' ? 'text-phosphor' : ($competencyTone === 'amber' ? 'text-amber' : ($competencyTone === 'cyan' ? 'text-cyan' : 'text-static')) }}">
                {{ $competencyState }}
            </strong>
            <span>{{ $competencySummary['name'] ?? 'No active skill area' }} →</span>
        </a>
        <a href="{{ route('achievements') }}" class="dashboard-status-cell">
            <span class="dashboard-status-label">ACHIEVEMENTS</span>
            <strong>{{ $learnerStats['achievements'] }}</strong>
            <span>{{ $learnerStats['achievements'] === 1 ? 'recognition unlocked' : 'recognitions unlocked' }} →</span>
        </a>
    </section>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[1.15fr_0.85fr]">
        <x-panel title="Recent learning activity">
            <div class="divide-y divide-phosphor/10">
                @forelse ($recentActivity as $activity)
                    <div class="flex min-w-0 items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <span class="min-w-0 text-sm leading-relaxed text-ink">{{ $activity->message }}</span>
                        <time class="shrink-0 font-code text-xs text-static">{{ $activity->created_at?->format('M d · H:i') }}</time>
                    </div>
                @empty
                    <p class="text-sm text-static">No learning activity recorded yet.</p>
                @endforelse
            </div>
            <a href="{{ route('timeline') }}" class="terminal-link mt-4 inline-flex">OPEN FULL TIMELINE →</a>
        </x-panel>

        <x-panel title="Learning progress">
            <dl class="space-y-3 text-sm">
                <div class="dashboard-signal-row">
                    <dt>Current course</dt>
                    <dd>{{ $course?->name ?? 'None active' }}</dd>
                </div>
                <div class="dashboard-signal-row">
                    <dt>Course progress</dt>
                    <dd>{{ $courseProgress === null ? '—' : $courseProgress['percent'].'%' }}</dd>
                </div>
                <div class="dashboard-signal-row">
                    <dt>Current position</dt>
                    <dd>{{ $resume['mission']->title ?? ($course === null ? 'All courses complete' : 'Boss milestone') }}</dd>
                </div>
            </dl>
            <a href="{{ route('progress') }}" class="terminal-link mt-4 inline-flex">OPEN COURSE RECORD →</a>
        </x-panel>
    </div>
@endsection
