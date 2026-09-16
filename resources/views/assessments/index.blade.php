@extends('layouts.app', ['role' => $role])

@section('title', 'Assessments')

@php
    $tones = [
        'none' => 'dim',
        'sealed' => 'amber',
        'ready' => 'cyan',
        'in-progress' => 'cyan',
        'submitted' => 'amber',
        'failed' => 'alert',
        'passed' => 'phosphor',
        'failed-retry' => 'phosphor',
    ];
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
@endphp

@section('content')
    <x-page-header
        title="Boss Challenges"
        subtitle="Final assessment for each active course"
        icon="◈"
    >
        <x-slot:actions>
            <x-badge tone="phosphor">XP {{ $totalXp }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if (session('assessment_locked'))
        <x-status-message type="info" title="{{ session('assessment_locked')['title'] }}">
            {{ session('assessment_locked')['message'] }}
        </x-status-message>
    @endif
    @if (session('assessment_error'))
        <x-status-message type="error" title="{{ session('assessment_error')['title'] }}">
            {{ session('assessment_error')['message'] }}
        </x-status-message>
    @endif

    <div class="space-y-4">
        @forelse ($rows as $row)
            @php
                $course = $row['course'];
                $assessment = $row['assessment'];
                $state = $row['state'];
                $tone = $tones[$state];
                $label = $labels[$state];
            @endphp
            <x-panel>
                <div class="flex flex-wrap items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="font-display text-base font-bold tracking-tight text-[#0a0a23]">{{ $course->name }}</p>
                        <p class="font-body text-[15px] text-ink mt-1">
                            {{ $assessment?->title ?? 'No Boss Challenge authored for this course.' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <x-badge tone="{{ $tone }}">{{ $label }}</x-badge>
                        @if ($assessment !== null)
                            <x-badge tone="dim">PASS ≥ {{ $assessment->passing_score }}%</x-badge>
                        @endif
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-3">
                    @if ($state === 'sealed')
                        <x-badge tone="amber">
                            {{ $row['missionProgress']['completed'] }}/{{ $row['missionProgress']['total'] }} MISSIONS DONE REQUIRED
                        </x-badge>
                    @elseif ($state === 'ready')
                        <form method="POST" action="{{ route('assessment.start', $assessment) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn-primary">INITIATE CHALLENGE</button>
                        </form>
                        <a href="{{ route('assessment.show', $assessment) }}" class="btn-ghost">VIEW BRIEFING</a>
                    @elseif ($state === 'in-progress')
                        <a href="{{ route('assessment.show', $assessment) }}" class="btn-primary">CONTINUE CHALLENGE</a>
                    @elseif ($state === 'submitted')
                        <a href="{{ route('assessment.show', $assessment) }}" class="btn-ghost">VIEW STATUS</a>
                    @elseif ($state === 'failed')
                        <form method="POST" action="{{ route('assessment.retry', $assessment) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn-primary">RETRY CHALLENGE</button>
                        </form>
                        <a href="{{ route('assessment.show', $assessment) }}" class="btn-ghost">REVIEW</a>
                    @elseif ($state === 'failed-retry')
                        <p class="font-body text-[15px] text-ink">
                            Last retry failed — your pass stands.
                        </p>
                        <a href="{{ route('assessment.show', $assessment) }}" class="btn-ghost">OPEN CHALLENGE</a>
                    @else
                        <p class="font-body text-[15px] text-ink">Course cleared.</p>
                        <a href="{{ route('assessment.show', $assessment) }}" class="btn-ghost">OPEN CHALLENGE</a>
                    @endif
                </div>
            </x-panel>
        @empty
            <x-status-message type="info" title="No courses">
                No active courses are assigned to your path.
            </x-status-message>
        @endforelse
    </div>
@endsection