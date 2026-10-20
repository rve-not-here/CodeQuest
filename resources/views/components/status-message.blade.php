@props(['type' => 'info', 'title' => '', 'dismissible' => false])

@php
    $map = [
        'success' => ['dot' => 'bg-accent', 'text' => 'text-accent', 'border' => 'border-accent-line/30'],
        'info' => ['dot' => 'bg-info', 'text' => 'text-info', 'border' => 'border-info/30'],
        'warning' => ['dot' => 'bg-warning', 'text' => 'text-warning', 'border' => 'border-warning/30'],
        'error' => ['dot' => 'bg-danger', 'text' => 'text-danger', 'border' => 'border-danger/30'],
    ];
    $cfg = $map[$type] ?? $map['info'];
@endphp

<div
    {{ $attributes->merge(['class' => 'flex items-start gap-3 border bg-surface-alt px-4 py-3 '.$cfg['border']]) }}
    data-status-message
    @if ($type === 'error') role="alert" @endif
>
    <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $cfg['dot'] }}" aria-hidden="true"></span>
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-bold text-ink">{{ $title }}</p>
        @endif
        <div class="text-[15px] leading-snug text-static">{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" class="ml-auto shrink-0 text-lg leading-none text-static hover:text-ink" data-dismiss aria-label="Dismiss"><x-icon name="x" size="16" /></button>
    @endif
</div>
