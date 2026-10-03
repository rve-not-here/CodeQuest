@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Competency')

@section('content')
    <div class="mx-auto w-full max-w-[1080px]">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
            <div>
                <p class="eyebrow">Learning record</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">Your Competencies</h1>
                <p class="mt-2 max-w-[64ch] text-sm leading-6 text-fg-muted">See the work behind each skill area and what you can practice next.</p>
            </div>
            <a href="{{ route('learning-path') }}" class="btn btn-secondary btn-sm">View Learning Path →</a>
        </header>

        @if ($competencies->isEmpty())
            <section class="panel mt-6 px-6 py-10 text-center" aria-labelledby="no-competencies-title">
                <h2 id="no-competencies-title" class="text-lg font-semibold">NO COMPETENCIES</h2>
                <p class="mt-2 text-sm text-fg-muted">No active skill areas with challenges are available yet.</p>
            </section>
        @else
            <section class="mt-6" aria-labelledby="course-competencies-title">
                <div class="mb-3">
                    <p class="eyebrow">Course evidence</p>
                    <h2 id="course-competencies-title" class="mt-1 text-lg font-semibold">Competency by course</h2>
                </div>
                <ol class="grid grid-cols-1 gap-4">
                    @foreach ($competencies as $row)
                        @php
                            $state = $row['state'];
                            $stateLabel = strtoupper(str_replace('_', ' ', $state));
                            $reason = match ($state) {
                                'demonstrated' => 'Demonstrated through: '.$row['totalMissions'].' completed challenges and a passed Boss Challenge.',
                                'practicing' => $row['attempts'] > 0
                                    ? 'Boss Challenge attempted '.$row['attempts'].' time(s), not yet passed.'
                                    : $row['completedMissions'].' of '.$row['totalMissions'].' challenges completed.',
                                'developing' => 'Work in progress: '.$row['completedMissions'].' of '.$row['totalMissions'].' challenges completed.',
                                default => 'No learning recorded for this area yet.',
                            };
                            $challengeLabel = $row['challengePassed'] ? 'PASSED' : ($row['attempts'] > 0 ? 'ATTEMPTED, NOT PASSED' : 'NOT ATTEMPTED');
                            $percent = $row['totalMissions'] > 0 ? (int) round($row['completedMissions'] / $row['totalMissions'] * 100) : 0;
                        @endphp
                        <li class="panel min-w-0 px-5 py-5 md:px-6" data-competency-state="{{ $state }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="eyebrow">Course skill area</p>
                                    <h3 class="mt-1 text-lg font-semibold text-balance">{{ $row['name'] }}</h3>
                                </div>
                                <span class="badge {{ $state === 'demonstrated' ? 'badge-accent' : ($state === 'practicing' ? 'badge-warning' : 'badge-neutral') }}">{{ $stateLabel }}</span>
                            </div>
                            <div class="mt-5 flex flex-wrap items-baseline justify-between gap-2 text-sm">
                                <span class="text-fg-muted"><span class="font-mono text-fg">{{ $row['completedMissions'] }}/{{ $row['totalMissions'] }}</span> challenges complete</span>
                                <span class="font-mono text-fg-muted">{{ $percent }}%</span>
                            </div>
                            <div class="progress mt-2" role="progressbar" aria-label="{{ $row['name'] }} challenge progress" aria-valuemin="0" aria-valuemax="{{ $row['totalMissions'] }}" aria-valuenow="{{ $row['completedMissions'] }}" aria-valuetext="{{ $row['completedMissions'] }} of {{ $row['totalMissions'] }} challenges complete">
                                <span style="width: {{ $percent }}%"></span>
                            </div>
                            <div class="mt-5 border-t border-line pt-4">
                                <p class="eyebrow">Why this result</p>
                                <p class="mt-1 text-sm leading-6 text-fg-muted">{{ $reason }}</p>
                                <p class="mt-3 text-xs text-fg-subtle">Boss Challenge: {{ $challengeLabel }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        @if ($skills->isNotEmpty())
            <section class="mt-8" aria-labelledby="skill-competency-title">
                <div class="mb-3">
                    <p class="eyebrow">Boss Challenge evidence</p>
                    <h2 id="skill-competency-title" class="mt-1 text-lg font-semibold">Skill Competency</h2>
                </div>
                <ul class="panel divide-y divide-line">
                    @foreach ($skills as $skill)
                        @php
                            $skillLabel = match ($skill['state']) {
                                'proficient' => 'ON TRACK',
                                'weak' => 'NEEDS WORK',
                                default => 'NOT ASSESSED',
                            };
                        @endphp
                        <li class="flex min-w-0 flex-wrap items-start justify-between gap-3 px-5 py-4 md:px-6">
                            <div class="min-w-0">
                                <h3 class="text-[15px] font-semibold text-balance">{{ $skill['label'] }}</h3>
                                @if ($skill['percentage'] === null)
                                    <p class="mt-1 text-xs text-fg-muted">No evidence recorded yet.</p>
                                @else
                                    <p class="mt-1 text-xs leading-5 text-fg-muted">{{ $skill['percentage'] }}% · {{ $skill['kcCorrect'] }}/{{ $skill['kcTotal'] }} checks · {{ $skill['challengesCompleted'] }}/{{ $skill['challengesApplicable'] }} challenges</p>
                                @endif
                            </div>
                            <span class="badge {{ $skill['state'] === 'proficient' ? 'badge-accent' : ($skill['state'] === 'weak' ? 'badge-warning' : 'badge-neutral') }}">{{ $skillLabel }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
