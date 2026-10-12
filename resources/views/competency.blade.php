@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Learning Performance')

@section('content')
    <div class="atlas-record">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
            <div>
                <p class="eyebrow">Learning record</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">Learning Performance</h1>
                <p class="mt-2 max-w-[64ch] text-sm leading-6 text-fg-muted">Integrated Competency Analysis. Read the recorded evidence behind your course and skill states.</p>
            </div>
            <a href="{{ route('learning-path') }}" class="btn btn-secondary btn-sm">View Learning Path →</a>
        </header>

        @if ($competencies->isEmpty())
            <section class="atlas-record-empty" aria-labelledby="no-competencies-title">
                <h2 id="no-competencies-title" class="text-lg font-semibold">NO COMPETENCIES</h2>
                <p class="mt-2 text-sm text-fg-muted">No active skill areas with challenges are available yet.</p>
            </section>
        @else
            <section class="mt-6" aria-labelledby="course-competencies-title">
                <div class="mb-3">
                    <p class="eyebrow">Course evidence</p>
                    <h2 id="course-competencies-title" class="mt-1 text-lg font-semibold">Competency by course</h2>
                </div>
                <ol class="atlas-record-list">
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
                        @endphp
                        <li class="atlas-course-record" data-competency-state="{{ $state }}">
                            <div class="atlas-course-context">
                                <h3 class="text-xl font-semibold text-balance">{{ $row['name'] }}</h3>
                                <span class="badge {{ $state === 'demonstrated' ? 'badge-accent' : ($state === 'practicing' ? 'badge-warning' : 'badge-neutral') }}">{{ $stateLabel }}</span>
                            </div>
                            <div class="atlas-course-evidence">
                                <p class="text-base leading-7 text-fg">{{ $reason }}</p>
                                <dl class="atlas-evidence-facts">
                                    <div>
                                        <dt>Coding Challenges</dt>
                                        <dd><span class="font-mono">{{ $row['completedMissions'] }}/{{ $row['totalMissions'] }}</span> complete</dd>
                                    </div>
                                    <div>
                                        <dt>Course assessment</dt>
                                        <dd>Boss Challenge: {{ $challengeLabel }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        @if ($skills->isNotEmpty())
            <section class="mt-8" aria-labelledby="skill-competency-title">
                <div class="mb-3">
                    <p class="eyebrow">Knowledge Check and Coding Challenge evidence</p>
                    <h2 id="skill-competency-title" class="mt-1 text-lg font-semibold">Skill Competency</h2>
                </div>
                <ul class="atlas-record-list">
                    @foreach ($skills as $skill)
                        @php
                            $skillLabel = match ($skill['state']) {
                                'proficient' => 'ON TRACK',
                                'weak' => 'NEEDS WORK',
                                default => 'NOT ASSESSED',
                            };
                        @endphp
                        <li class="atlas-skill-record">
                            <div class="min-w-0">
                                <h3 class="text-[15px] font-semibold text-balance">{{ $skill['label'] }}</h3>
                                @if ($skill['percentage'] === null)
                                    <p class="mt-1 text-xs text-fg-muted">No evidence recorded yet.</p>
                                @else
                                    <p class="mt-1 text-xs leading-5 text-fg-muted">Recorded skill result: {{ $skill['percentage'] }}%</p>
                                @endif
                                <dl class="atlas-evidence-facts">
                                    <div><dt>Knowledge Checks</dt><dd>{{ $skill['kcTotal'] > 0 ? $skill['kcCorrect'].'/'.$skill['kcTotal'].' correct' : 'No evidence recorded' }}</dd></div>
                                    <div><dt>Coding Challenges</dt><dd>{{ $skill['challengesCompleted'] }}/{{ $skill['challengesApplicable'] }} complete</dd></div>
                                </dl>
                            </div>
                            <span class="badge {{ $skill['state'] === 'proficient' ? 'badge-accent' : ($skill['state'] === 'weak' ? 'badge-warning' : 'badge-neutral') }}">{{ $skillLabel }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
