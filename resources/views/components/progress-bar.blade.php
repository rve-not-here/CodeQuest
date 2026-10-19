@props(['label' => '', 'total' => 100, 'current' => 0, 'tone' => 'phosphor'])

@php
    $pct = $total > 0 ? max(0, min(100, round(($current / $total) * 100))) : 0;

    $bar = match ($tone) {
        'amber' => 'bg-warning',
        'alert' => 'bg-danger',
        'cyan' => 'bg-info',
        'phosphor' => 'bg-success',
        'dim' => 'bg-fg-muted',
        default => 'bg-success',
    };
@endphp

<div class="w-full">
    @if ($label)
        <div class="flex items-baseline justify-between gap-3 mb-1.5">
            <span class="text-sm font-semibold text-ink">{{ $label }}</span>
            <span class="font-code text-sm text-static">{{ $pct }}%</span>
        </div>
    @endif

    <div class="h-1.5 w-full overflow-hidden border border-line bg-raised">
        <div
            class="h-full {{ $bar }} transition-all duration-200"
            style="width: {{ $pct }}%"
            role="progressbar"
            aria-valuenow="{{ $pct }}"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label="{{ $label ?: 'progress' }}"
        ></div>
    </div>
</div>
