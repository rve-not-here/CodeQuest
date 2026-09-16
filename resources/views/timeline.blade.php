@extends('layouts.app', ['role' => $role])

@section('title', 'Timeline')

@section('content')
    <x-page-header
        title="Learning Timeline"
        subtitle="Chronological record of learning activity. Login and logout are not logged."
        icon="≡"
    >
        <x-slot:actions>
            <x-badge tone="phosphor">XP {{ $totalXp }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if ($events->isEmpty())
        <x-status-message type="info" title="NO LEARNING EVENTS">
            No learning activity recorded yet. Complete missions and Boss Challenges to populate your timeline.
        </x-status-message>
    @endif

    <x-panel title="EVENT LOG">
        <div class="divide-y divide-phosphor-dim/40">
            @foreach ($events as $event)
                @php
                    [$icon, $tone] = match ($event['type']) {
                        'mission_completed' => ['⚡', 'text-phosphor'],
                        'wrong_submission' => ['✕', 'text-alert'],
                        'hint_used' => ['◈', 'text-cyan'],
                        'solution_revealed' => ['◎', 'text-amber'],
                        'assessment_completed', 'assessment_passed' => ['◆', 'text-phosphor'],
                        'assessment_failed' => ['◈', 'text-alert'],
                        'section_completed' => ['▦', 'text-amber'],
                        default => ['·', 'text-phosphor-dim'],
                    };
                @endphp
                <div class="flex items-center gap-3 py-2">
                    <span class="font-display text-[14px] leading-none {{ $tone }} w-4 shrink-0 text-center">{{ $icon }}</span>
                    <span class="font-body text-[15px] text-ink truncate min-w-0">{{ $event['label'] }}</span>
                    @if ($event['pts'] !== null && $event['pts'] !== 0)
                        <x-badge tone="{{ $event['pts'] > 0 ? 'phosphor' : 'alert' }}">
                            {{ $event['pts'] > 0 ? '+' : '' }}{{ $event['pts'] }} XP
                        </x-badge>
                    @endif
                    <span class="text-xs font-bold text-phosphor-dim shrink-0 ml-auto">
                        {{ $event['at']->format('M d, H:i') }}
                    </span>
                </div>
            @endforeach
        </div>
    </x-panel>
@endsection