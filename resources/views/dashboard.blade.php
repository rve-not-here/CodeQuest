@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@php
    $displayName = auth()->user()?->username ?? 'Student';
    $firstName = strtok($displayName, ' ') ?: $displayName;
    $mission = $resume !== null && $resume['type'] === 'mission' ? $resume['mission'] : null;
    $section = $resume['section'] ?? null;
    $competencyState = $competencySummary === null
        ? 'NO EVIDENCE YET'
        : strtoupper(str_replace('_', ' ', $competencySummary['state']));
@endphp

@section('title', 'Dashboard')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="eyebrow">Student dashboard</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance">Welcome back, {{ $firstName }}</h1>
        </div>
    </div>

    <section aria-labelledby="continue-heading" class="panel overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="relative p-5 md:p-7">
                <span class="absolute inset-y-0 left-0 w-0.5 bg-accent" aria-hidden="true"></span>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge {{ $course === null ? 'badge-neutral' : 'badge-accent' }}">
                        {{ $course === null ? 'Course complete' : ($mission !== null ? 'Current mission' : 'Course milestone') }}
                    </span>
                    @if ($course !== null)
                        <span class="font-mono text-xs text-fg-subtle">{{ $course->name }}@if ($section !== null) / {{ $section->title }}@endif</span>
                    @endif
                </div>

                @if ($course === null)
                    <h2 id="continue-heading" class="mt-4 text-xl font-semibold tracking-tight text-balance md:text-2xl">Course complete</h2>
                    <p class="mt-2 max-w-[62ch] text-fg-muted">You have cleared every active course. Review your learning record.</p>
                @elseif ($mission !== null)
                    <h2 id="continue-heading" class="mt-4 text-xl font-semibold tracking-tight text-balance md:text-2xl">{{ $mission->title }}</h2>
                    <p class="mt-2 max-w-[62ch] text-fg-muted">{{ $resumeHasDraft ? 'Saved work detected. Your draft is ready to continue.' : 'Open the lesson, then work through the coding challenge.' }}</p>
                    <dl class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                        <div class="flex items-center gap-2"><dt class="eyebrow">Type</dt><dd>Coding challenge</dd></div>
                        <div class="flex items-center gap-2"><dt class="eyebrow">Difficulty</dt><dd>{{ ucfirst($mission->difficulty) }}</dd></div>
                        <div class="flex items-center gap-2"><dt class="eyebrow">Reward</dt><dd class="font-mono text-accent">+{{ $mission->points }} XP</dd></div>
                    </dl>
                @else
                    <h2 id="continue-heading" class="mt-4 text-xl font-semibold tracking-tight text-balance md:text-2xl">Boss Challenge ready</h2>
                    <p class="mt-2 max-w-[62ch] text-fg-muted">Return to the Learning Path to inspect the course milestone.</p>
                @endif

                @if ($courseProgress !== null)
                    <div class="mt-6 max-w-md">
                        <div class="mb-2 flex items-baseline justify-between gap-2 text-sm">
                            <span class="text-fg-muted">{{ $course->name }} progress</span>
                            <span class="font-mono text-xs text-fg">{{ $courseProgress['completed'] }} / {{ $courseProgress['total'] }} missions</span>
                        </div>
                        <div class="progress" role="progressbar" aria-label="{{ $course->name }} progress" aria-valuemin="0" aria-valuemax="{{ $courseProgress['total'] }}" aria-valuenow="{{ $courseProgress['completed'] }}" aria-valuetext="{{ $courseProgress['completed'] }} of {{ $courseProgress['total'] }} missions complete">
                            <span style="width: {{ $courseProgress['percent'] }}%"></span>
                        </div>
                    </div>
                @endif

                <div class="mt-7 flex flex-wrap gap-2.5">
                    @if ($mission !== null)
                        <a href="{{ route($resumeHasDraft ? 'mission.challenge' : 'mission.show', $mission) }}" class="btn btn-primary btn-lg">{{ $resumeHasDraft ? 'Resume Challenge' : 'Continue Learning' }} →</a>
                        <a href="{{ route('learning-path') }}" class="btn btn-secondary btn-lg">View Learning Path</a>
                    @else
                        <a href="{{ route('learning-path') }}" class="btn btn-primary btn-lg">{{ $course === null ? 'View Learning Path' : 'Continue Learning' }} →</a>
                    @endif
                </div>
            </div>

            <aside aria-labelledby="next-heading" class="border-t border-line bg-raised/40 p-5 md:p-6 lg:border-t-0 lg:border-l">
                <h3 id="next-heading" class="eyebrow">Your learning path</h3>
                <p class="mt-3 text-sm font-medium text-fg">{{ $course?->name ?? 'All active courses complete' }}</p>
                <p class="mt-1 text-sm leading-6 text-fg-muted">
                    @if ($mission !== null)
                        {{ $section?->title ?? 'Next available mission' }}
                    @elseif ($course !== null)
                        The course assessment is the next milestone.
                    @else
                        Review your completed work and learning record.
                    @endif
                </p>
                <div class="mt-5 border-t border-line pt-4">
                    <a href="{{ route('learning-path') }}" class="text-sm font-medium text-info hover:underline">Open course and section details →</a>
                </div>
            </aside>
        </div>
    </section>

    <section aria-labelledby="summary-heading" class="mt-4">
        <h2 id="summary-heading" class="sr-only">Progress summary</h2>
        <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-md border border-line bg-line lg:grid-cols-4">
            <div class="min-w-0 bg-surface px-4 py-3.5 md:px-5">
                <dt class="eyebrow">Course progress</dt>
                <dd class="mt-1.5 font-mono text-lg font-medium text-fg">{{ $courseProgress === null ? 'Complete' : $courseProgress['percent'].'%' }}</dd>
                <dd class="text-[13px] text-fg-subtle">{{ $courseProgress === null ? 'All active courses' : $courseProgress['completed'].' of '.$courseProgress['total'].' missions' }}</dd>
            </div>
            <div class="min-w-0 bg-surface px-4 py-3.5 md:px-5">
                <dt class="eyebrow">Competency</dt>
                <dd class="mt-1.5 text-[15px] font-medium text-fg">{{ $competencySummary['name'] ?? 'No active skill area' }}</dd>
                <dd class="text-[13px] text-info">{{ $competencyState }}</dd>
            </div>
            <div class="min-w-0 bg-surface px-4 py-3.5 md:px-5">
                <dt class="eyebrow">Boss Challenges</dt>
                <dd class="mt-1.5 font-mono text-lg font-medium text-fg">{{ $learnerStats['boss_challenges_passed'] }}</dd>
                <dd class="text-[13px] text-fg-subtle">passed</dd>
            </div>
            <div class="min-w-0 bg-surface px-4 py-3.5 md:px-5">
                <dt class="eyebrow">XP BALANCE</dt>
                <dd class="mt-1.5 font-mono text-lg font-medium text-fg"><strong>{{ number_format($totalXp) }}</strong></dd>
                <dd><a href="{{ route('xp-ledger') }}" class="text-[13px] text-info hover:underline">View XP ledger →</a></dd>
            </div>
        </dl>
    </section>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="flex min-w-0 flex-col gap-6 lg:col-span-2">
            <section aria-labelledby="course-heading" class="panel">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-3.5">
                    <div>
                        <h2 id="course-heading" class="text-[15px] font-semibold">{{ $course?->name ?? 'Learning path' }}</h2>
                        <p class="font-mono text-2xs text-fg-subtle">COURSE · MISSIONS · BOSS CHALLENGE</p>
                    </div>
                    <a href="{{ route('learning-path') }}" class="btn btn-ghost btn-sm">Open path →</a>
                </div>
                <div class="px-5 py-4 text-sm text-fg-muted">
                    @if ($courseProgress !== null)
                        <p>{{ $courseProgress['completed'] }} of {{ $courseProgress['total'] }} required missions complete.</p>
                        <p class="mt-1">{{ $mission !== null ? 'Continue the next mission to advance.' : 'Review the Boss Challenge milestone in the learning path.' }}</p>
                    @else
                        <p>Review the courses and challenges you completed.</p>
                    @endif
                </div>
            </section>

            <section aria-labelledby="activity-heading" class="panel">
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5">
                    <h2 id="activity-heading" class="text-[15px] font-semibold">Recent activity</h2>
                    <a href="{{ route('timeline') }}" class="btn btn-ghost btn-sm">View timeline →</a>
                </div>
                <ol class="divide-y divide-line">
                    @forelse ($recentActivity as $activity)
                        <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 px-5 py-3">
                            <span class="min-w-0 text-sm text-fg">{{ $activity->message }}</span>
                            <time class="shrink-0 font-mono text-2xs text-fg-subtle" datetime="{{ $activity->created_at?->toIso8601String() }}">{{ $activity->created_at?->format('M d · H:i') }}</time>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-fg-muted">No learning activity recorded yet.</li>
                    @endforelse
                </ol>
            </section>
        </div>

        <div class="flex min-w-0 flex-col gap-6">
            <section aria-labelledby="competency-heading" class="panel">
                <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5">
                    <h2 id="competency-heading" class="text-[15px] font-semibold">Competency</h2>
                    <a href="{{ route('competency') }}" class="btn btn-ghost btn-sm">Details →</a>
                </div>
                <div class="px-5 py-4">
                    <p class="text-sm font-medium text-fg">{{ $competencySummary['name'] ?? 'No active skill area' }}</p>
                    <p class="mt-1 text-sm text-fg-muted">{{ $competencyState }}</p>
                </div>
            </section>
            <section aria-labelledby="next-action-heading" class="panel">
                <div class="border-b border-line px-5 py-3.5">
                    <h2 id="next-action-heading" class="text-[15px] font-semibold">Next steps</h2>
                </div>
                <div class="space-y-3 px-5 py-4 text-sm">
                    <p class="text-fg-muted">Recommendations use your recorded learning activity.</p>
                    <a href="{{ route('recommendations') }}" class="inline-block font-medium text-info hover:underline">View recommendations →</a>
                    <div class="border-t border-line pt-3">
                        <a href="{{ route('achievements') }}" class="text-fg-muted hover:text-fg">{{ $learnerStats['achievements'] }} {{ $learnerStats['achievements'] === 1 ? 'achievement' : 'achievements' }} earned →</a>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
