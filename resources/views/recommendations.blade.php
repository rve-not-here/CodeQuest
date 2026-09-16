@extends('layouts.app', ['role' => $role])

@section('title', 'Recommendations')

@section('content')
    <x-page-header
        title="Recommendations"
        subtitle="Deterministic next actions derived from your own progress. No fortune-telling."
        icon="✴"
    />

    @if ($recommendations->isEmpty())
        <x-status-message type="info" title="NO RECOMMENDATIONS">
            No recommendations right now. Complete missions and Boss Challenges to generate suggestions.
        </x-status-message>
    @endif

    <x-panel title="NEXT ACTIONS">
        <div class="divide-y divide-phosphor-dim/40">
            @foreach ($recommendations as $recommendation)
                <div class="flex items-center gap-3 py-3">
                    <x-badge tone="{{ $recommendation['slot'] === 3 ? 'amber' : 'phosphor' }}">
                        PRIORITY {{ $recommendation['slot'] }}
                    </x-badge>
                    <div class="min-w-0">
                        <p class="font-body text-[15px] text-ink truncate">{{ $recommendation['title'] }}</p>
                        <p class="text-xs font-bold text-phosphor-dim truncate">{{ $recommendation['subtitle'] }}</p>
                    </div>
                    <a
                        href="{{ $recommendation['href'] }}"
                        class="ml-auto shrink-0 text-sm font-bold border border-phosphor-dim text-phosphor hover:text-void hover:bg-phosphor px-3 py-1 rounded-[2px]"
                    >
                        {{ $recommendation['cta'] }} →
                    </a>
                </div>
            @endforeach
        </div>
    </x-panel>
@endsection