@extends('layouts.app', ['role' => $role])

@section('title', $mission->title)

@section('content')
    @if ($completed)
        <x-status-message type="success" title="MISSION COMPLETE" dismissible>
            You have already passed this mission.
        </x-status-message>
    @endif
    @if (session('mission_success'))
        <x-status-message type="success" title="MISSION COMPLETE" dismissible>
            {{ session('mission_success')['message'] }}
        </x-status-message>
    @endif
    @if (session('mission_info'))
        <x-status-message type="info" title="{{ session('mission_info')['title'] }}">
            {{ session('mission_info')['message'] }}
        </x-status-message>
    @endif
    @if (session('mission_error'))
        <x-status-message type="error" title="{{ session('mission_error')['title'] }}">
            {{ session('mission_error')['message'] }}
        </x-status-message>
    @endif

    <nav class="mb-3 text-xs font-bold text-phosphor-dim uppercase tracking-wide flex flex-wrap items-center gap-1">
        @if ($course)
            <span>{{ $course->name }}</span>
        @endif
        @if ($section)
            <span aria-hidden="true">/</span>
            <span>{{ $section->title }}</span>
        @endif
    </nav>

    <x-page-header :title="$mission->title" subtitle="Concept lesson. Complete the Challenge to earn XP.">
        <x-slot:actions>
            <x-badge tone="{{ $mission->difficulty === 'HARD' ? 'alert' : ($mission->difficulty === 'MEDIUM' ? 'amber' : 'cyan') }}">
                {{ $mission->difficulty }}
            </x-badge>
            <x-badge tone="phosphor">PASS: +{{ $mission->points }} XP</x-badge>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-4 mb-6">
        <x-panel title="LEARNING OBJECTIVE">
            @if ($mission->description)
                <p class="font-body text-[15px] leading-snug text-ink">{{ $mission->description }}</p>
            @else
                <p class="font-body text-[15px] leading-snug text-ink">No briefing attached to this mission.</p>
            @endif
        </x-panel>

        @if ($mission->target_html)
            <x-panel title="EXPECTED OUTPUT">
                <p class="mb-3 text-xs font-bold text-phosphor-dim">THE PATTERN THIS CHALLENGE AIMS FOR.</p>
                <pre class="bg-[#0a0a23] text-[#d0e0d5] text-sm leading-snug p-4 rounded-[2px] overflow-x-auto">{{ $mission->target_html }}</pre>
            </x-panel>
        @endif

        @if ($mission->broken_code)
            <x-panel title="TRY IT YOURSELF — STARTER">
                <p class="mb-3 text-xs font-bold text-amber">
                    PRACTICE ZONE, NOT GRADED. LIVE EDITING AND RUNNING HAPPEN IN THE CHALLENGE WORKSPACE.
                </p>
                <pre class="bg-[#0a0a23] text-[#d0e0d5] text-sm leading-snug p-4 rounded-[2px] overflow-x-auto">{{ $mission->broken_code }}</pre>
            </x-panel>
        @endif

        <x-panel title="MISSION NOTES">
            <p class="font-body text-[15px] leading-snug text-ink">
                A wrong submission costs {{ $wrongPenalty }} XP. Earn the PASS reward by satisfying the Challenge's grading rules.
            </p>
        </x-panel>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('mission.challenge', $mission) }}" class="btn-primary">START CHALLENGE →</a>
        <a href="{{ route('learning-path') }}" class="btn-ghost">BACK TO LEARNING PATH</a>
    </div>
@endsection