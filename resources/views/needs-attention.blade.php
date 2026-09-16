@extends('layouts.app', ['role' => $role])

@section('title', 'Needs Attention')

@section('content')
    <x-page-header
        title="Needs Attention"
        subtitle="Students with at least one deterministic attention signal. Each signal is binary, has a named threshold, and shows its evidence. This is not a grade: there is no composite score, only reasons."
        icon="⚑"
    >
        <x-slot:actions>
            <x-badge tone="cyan">SYSTEM-WIDE</x-badge>
        </x-slot:actions>
    </x-page-header>

    @php($chipClasses = [
        'boss_fail' => 'text-amber border-amber',
        'repeat_fail' => 'text-amber border-amber',
        'low_performance' => 'text-amber border-amber',
        'stalled' => 'text-cyan border-cyan',
        'never_started' => 'text-phosphor border-phosphor-dim',
        'inactive' => 'text-phosphor border-phosphor-dim',
    ])

    @forelse ($students as $row)
        <section class="panel p-4 mb-4">
            <header class="flex flex-wrap items-center justify-between gap-2 mb-3 pb-2 border-b border-phosphor-dim/60">
                <div class="flex items-center gap-2 min-w-0">
                    <h2 class="panel-title truncate">{{ strtoupper($row['student']->username) }}</h2>
                    @if ($row['current_course'])
                        <span class="text-xs font-bold text-phosphor-dim shrink-0">
                            @ {{ strtoupper($row['current_course']->name) }}
                        </span>
                    @endif
                </div>
                <span class="text-sm font-bold text-phosphor-dim shrink-0">
                    {{ count($row['signals']) }} SIGNAL{{ count($row['signals']) > 1 ? 'S' : '' }}
                </span>
            </header>

            <div class="flex flex-wrap items-center gap-2 mb-3">
                @foreach ($row['signals'] as $signal)
                    <span class="inline-block text-xs font-bold border px-2 py-1 rounded-[2px] leading-none {{ $chipClasses[$signal] }}">
                        {{ \App\Services\AttentionService::SIGNAL_LABELS[$signal] }}
                    </span>
                @endforeach
            </div>

            <p class="font-body text-[15px] leading-snug text-ink">
                <span class="text-sm font-bold text-phosphor-dim">PRIMARY</span>
                &nbsp;{{ $row['reasons'][$row['primary']] }}
            </p>

            @if (count($row['signals']) > 1)
                <details class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 mt-2 bg-surface-alt">
                    <summary class="text-sm font-bold text-phosphor cursor-pointer select-none">
                        OTHER SIGNALS
                    </summary>
                    <ul class="mt-2 space-y-1">
                        @foreach ($row['signals'] as $signal)
                            @continue($signal === $row['primary'])
                            <li class="font-body text-[14px] leading-snug text-ink">
                                <span class="text-xs font-bold text-amber">{{ \App\Services\AttentionService::SIGNAL_LABELS[$signal] }}</span>
                                &nbsp;{{ $row['reasons'][$signal] }}
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </section>
    @empty
        <section class="panel p-4">
            <x-status-message type="info" title="No signals">
                No student currently triggers an attention signal.
            </x-status-message>
        </section>
    @endforelse
@endsection