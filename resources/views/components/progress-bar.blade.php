@props(['label' => '', 'total' => 100, 'current' => 0, 'tone' => 'phosphor'])

@php
    $pct = $total > 0 ? max(0, min(100, round(($current / $total) * 100))) : 0;

    $bar = match ($tone) {
        'amber' => 'bg-amber',
        'alert' => 'bg-alert',
        'cyan' => 'bg-cyan',
        'phosphor' => 'bg-[#198eee]',
        default => 'bg-[#198eee]',
    };
@endphp

<div class="w-full">
    @if ($label)
        <div class="flex items-baseline justify-between gap-3 mb-1.5">
            <span class="text-sm font-semibold text-[#2a2a40]">{{ $label }}</span>
            <span class="text-sm text-[#6f6f79]">{{ $pct }}%</span>
        </div>
    @endif

    <div class="h-2.5 w-full bg-[#f0f0f5] overflow-hidden rounded-[2px]">
        <div
            class="h-full {{ $bar }} transition-all duration-500"
            style="width: {{ $pct }}%"
            role="progressbar"
            aria-valuenow="{{ $pct }}"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label="{{ $label ?: 'progress' }}"
        ></div>
    </div>
</div>