@extends('layouts.app', ['role' => $role])

@section('title', 'Competency')

@section('content')
    <x-page-header
        title="Competency"
        subtitle="Skill areas derived from real learning and assessment data. Points are not competency."
    >
    </x-page-header>

    @if ($competencies->isEmpty())
        <x-status-message type="info" title="NO COMPETENCIES">
            No active skill areas are loaded. Awaiting new directives from Command.
        </x-status-message>
    @endif

    <div class="space-y-4">
        @foreach ($competencies as $row)            @php
                $state = $row['state'];
                $stateLabel = strtoupper(str_replace('_', ' ', $state));
                $stateTone = match ($state) {
                    'demonstrated' => 'phosphor',
                    'practicing' => 'amber',
                    'developing' => 'cyan',
                    default => 'dim',
                };
                $reason = match ($state) {
                    'demonstrated' => 'Demonstrated through: '.$row['totalMissions'].' completed challenges and a passed Boss Challenge.',
                    'practicing' => $row['attempts'] > 0
                        ? 'Boss Challenge attempted '.$row['attempts'].' time(s), not yet passed.'
                        : $row['completedMissions'].' of '.$row['totalMissions'].' challenges completed.',
                    'developing' => 'Work in progress: '.$row['completedMissions'].' of '.$row['totalMissions'].' challenges completed.',
                    default => 'No learning recorded for this area yet.',
                };
                $challenge = $row['challengePassed']
                    ? ['label' => 'PASSED', 'tone' => 'phosphor']
                    : ($row['attempts'] > 0 ? ['label' => 'ATTEMPTED, NOT PASSED', 'tone' => 'amber'] : ['label' => 'NOT ATTEMPTED', 'tone' => 'dim']);
            @endphp

            <x-panel title="{{ $row['name'] }}">
                <x-slot:actions>
                    <x-badge tone="{{ $stateTone }}">
                        {{ $row['completedMissions'] }}/{{ $row['totalMissions'] }}
                    </x-badge>
                    <x-badge tone="{{ $stateTone }}">{{ $stateLabel }}</x-badge>
                </x-slot:actions>

                <x-progress-bar
                    label="Challenges Completed"
                    :total="$row['totalMissions']"
                    :current="$row['completedMissions']"
                    tone="{{ $stateTone }}"
                />

                <div class="mt-4 border border-phosphor-dim/40 p-3">
                    <p class="text-xs font-bold text-phosphor-dim mb-1">Why this value</p>
                    <p class="font-body text-[15px] text-ink">{{ $reason }}</p>

                    <x-badge tone="{{ $challenge['tone'] }}" class="mt-2">Boss Challenge: {{ $challenge['label'] }}</x-badge>
                </div>
            </x-panel>
        @endforeach
    </div>

    @if ($skills->isNotEmpty())
        <x-panel title="SKILL COMPETENCY" class="mt-6">
            <div class="divide-y divide-phosphor-dim/40">
                @foreach ($skills as $skill)
                    @php
                        $skillTone = match ($skill['state']) {
                            'proficient' => 'phosphor',
                            'weak' => 'amber',
                            default => 'dim',
                        };
                        $skillLabel = match ($skill['state']) {
                            'proficient' => 'ON TRACK',
                            'weak' => 'NEEDS WORK',
                            default => 'NOT ASSESSED',
                        };
                    @endphp
                    <div class="flex items-center gap-3 py-3">
                        <div class="min-w-0">
                            <p class="font-body text-[15px] text-ink truncate">{{ $skill['label'] }}</p>
                            <p class="text-xs font-bold text-phosphor-dim truncate">
                                @if ($skill['percentage'] === null)
                                    No evidence recorded yet.
                                @else
                                    {{ $skill['percentage'] }}% · {{ $skill['kcCorrect'] }}/{{ $skill['kcTotal'] }} checks · {{ $skill['challengesCompleted'] }}/{{ $skill['challengesApplicable'] }} challenges
                                @endif
                            </p>
                        </div>
                        <span class="ml-auto shrink-0">
                            <x-badge tone="{{ $skillTone }}">{{ $skillLabel }}</x-badge>
                        </span>
                    </div>
                @endforeach
            </div>
        </x-panel>
    @endif
@endsection