@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Learning Performance')

@section('content')
    @php
        $courseStates = [
            'demonstrated' => ['label' => 'Demonstrated', 'stamp' => 'atlas-stamp--grn'],
            'practicing' => ['label' => 'Practicing', 'stamp' => 'atlas-stamp--amb'],
            'developing' => ['label' => 'Developing', 'stamp' => 'atlas-stamp--vio'],
            'not_started' => ['label' => 'Not started', 'stamp' => 'atlas-stamp--ink'],
        ];
        $skillStates = [
            'proficient' => ['label' => 'On track', 'stamp' => 'atlas-stamp--grn'],
            'weak' => ['label' => 'Needs work', 'stamp' => 'atlas-stamp--amb'],
            'unassessed' => ['label' => 'Not assessed', 'stamp' => 'atlas-stamp--ink'],
        ];
        $demonstrated = $competencies->where('state', 'demonstrated')->count();
    @endphp
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Learning performance</span>
            <a href="{{ route('learning-path') }}">Learning path →</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Competency</span>
            <h1 class="atlas-records-title">Learning performance</h1>
            <p class="atlas-records-lede">The recorded evidence behind every course and skill state.</p>
            @if ($competencies->isNotEmpty() || $skills->isNotEmpty())
                <dl class="log-facts atlas-records-facts">
                    <div>
                        <dt>Courses</dt>
                        <dd class="is-numeric">{{ $competencies->count() }}</dd>
                    </div>
                    <div>
                        <dt>Demonstrated</dt>
                        <dd class="is-numeric">{{ $demonstrated }}</dd>
                    </div>
                    <div>
                        <dt>Skills tracked</dt>
                        <dd class="is-numeric">{{ $skills->count() }}</dd>
                    </div>
                </dl>
            @endif
        </header>

        @if ($competencies->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="no-competencies-title">
                <h2 id="no-competencies-title" class="atlas-records-empty-title">No competencies yet</h2>
                <p class="atlas-records-empty-copy">No active skill areas with challenges are available yet.</p>
            </section>
        @else
            <h2 class="atlas-records-subheading">Competency by course</h2>
            <ol class="atlas-records-list">
                @foreach ($competencies as $row)
                    @php
                        $state = $row['state'];
                        $stateView = $courseStates[$state] ?? $courseStates['not_started'];
                        $reason = match ($state) {
                            'demonstrated' => 'Demonstrated through: '.$row['totalMissions'].' completed challenges and a passed Boss Challenge.',
                            'practicing' => $row['attempts'] > 0
                                ? 'Boss Challenge attempted '.(int) $row['attempts'].' '.Str::plural('time', (int) $row['attempts']).', not yet passed.'
                                : $row['completedMissions'].' of '.$row['totalMissions'].' challenges completed.',
                            'developing' => 'Work in progress: '.$row['completedMissions'].' of '.$row['totalMissions'].' challenges completed.',
                            default => 'No learning recorded for this area yet.',
                        };
                        $bossState = $row['challengePassed'] ? 'Passed' : ($row['attempts'] > 0 ? 'Attempted, not passed' : 'Not attempted');
                    @endphp
                    <li class="atlas-record-entry" data-competency-state="{{ $state }}">
                        <div class="atlas-record-entry-head">
                            <span class="atlas-record-entry-label">Course competency</span>
                            <span class="atlas-stamp {{ $stateView['stamp'] }}">{{ $stateView['label'] }}</span>
                        </div>
                        <h3 class="atlas-record-entry-title">{{ $row['name'] }}</h3>
                        <p class="atlas-record-entry-copy">{{ $reason }}</p>
                        <dl class="log-facts atlas-record-entry-facts">
                            <div>
                                <dt>Challenges</dt>
                                <dd class="is-numeric">{{ $row['completedMissions'] }}/{{ $row['totalMissions'] }} complete</dd>
                            </div>
                            <div>
                                <dt>Boss Challenge</dt>
                                <dd>{{ $bossState }}</dd>
                            </div>
                        </dl>
                    </li>
                @endforeach
            </ol>
        @endif

        @if ($skills->isNotEmpty())
            <h2 class="atlas-records-subheading">Skill competency</h2>
            <p class="atlas-record-group-note">Knowledge Check and Coding Challenge evidence</p>
            <ul class="atlas-records-list">
                @foreach ($skills as $skill)
                    @php($skillView = $skillStates[$skill['state']] ?? $skillStates['unassessed'])
                    <li class="atlas-record-entry atlas-record-entry--compact" data-skill-state="{{ $skill['state'] }}">
                        <div class="atlas-record-entry-row atlas-record-entry-row--tag">
                            <h3 class="atlas-record-entry-title">{{ $skill['label'] }}</h3>
                            <span class="atlas-stamp {{ $skillView['stamp'] }}">{{ $skillView['label'] }}</span>
                        </div>
                        <p class="atlas-record-meta">
                            <span>Knowledge Checks {{ $skill['kcTotal'] > 0 ? $skill['kcCorrect'].'/'.$skill['kcTotal'].' correct' : 'no evidence recorded' }}</span>
                            <span>Coding Challenges {{ $skill['challengesCompleted'] }}/{{ $skill['challengesApplicable'] }} complete</span>
                            @if ($skill['percentage'] !== null)
                                <span>Recorded result {{ $skill['percentage'] }}%</span>
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
