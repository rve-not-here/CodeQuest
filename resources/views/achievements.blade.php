@extends('layouts.app', ['role' => $role])

@section('title', 'Achievements')

@section('content')
    <x-page-header
        title="Achievements"
        subtitle="Operators earn recognition for real learning progress. Every award is recorded server-side from actual progress and assessment data."
    >
        <x-slot:actions>
            <x-badge tone="phosphor">{{ $earnedCount }}/{{ $totalCount }} UNLOCKED</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if ($catalog->isEmpty())
        <x-status-message type="info" title="NO ACHIEVEMENTS">
            The achievement catalog is empty. Awaiting new directives from Command.
        </x-status-message>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($catalog as $row)
            @php
                $icon = match ($row['slug']) {
                    'first_challenge' => '⚡',
                    'first_course' => '◈',
                    'streak_3' => '⟳',
                    'full_clear' => '◎',
                    default => '◆',
                };
            @endphp
            <x-panel :title="$row['name']">
                <x-slot:actions>
                    @if ($row['awarded'])
                        <x-badge tone="phosphor">UNLOCKED</x-badge>
                    @else
                        <x-badge tone="dim">DISCOVERED</x-badge>
                    @endif
                </x-slot:actions>

                <div class="flex items-start gap-3">
                    <span class="font-display text-[22px] leading-none {{ $row['awarded'] ? 'text-phosphor' : 'text-phosphor-dim/50' }}" aria-hidden="true">
                        {{ $icon }}
                    </span>
                    <div class="min-w-0">
                        <p class="font-body text-[15px] text-ink leading-snug">
                            {{ $row['description'] ?? 'Achievement description pending.' }}
                        </p>
                        @if ($row['awarded'] && $row['unlocked_at'] !== null)
                            <p class="text-xs font-bold text-phosphor-dim mt-2">
                                EARNED {{ $row['unlocked_at']->format('Y-m-d H:i') }}
                            </p>
                        @else
                            <p class="text-xs font-bold text-phosphor-dim mt-2">
                                Keep learning to unlock this achievement.
                            </p>
                        @endif
                    </div>
                </div>
            </x-panel>
        @endforeach
    </div>
@endsection