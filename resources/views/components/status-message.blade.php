@props(['type' => 'info', 'title' => '', 'dismissible' => false])

@php
    $map = [
        'success' => ['dot' => 'bg-phosphor', 'text' => 'text-phosphor', 'border' => 'border-phosphor/30'],
        'info' => ['dot' => 'bg-cyan', 'text' => 'text-cyan', 'border' => 'border-cyan/30'],
        'warning' => ['dot' => 'bg-amber', 'text' => 'text-amber', 'border' => 'border-amber/30'],
        'error' => ['dot' => 'bg-alert', 'text' => 'text-alert', 'border' => 'border-alert/30'],
    ];
    $cfg = $map[$type] ?? $map['info'];
@endphp

<div
    {{ $attributes->merge(['class' => 'flex items-start gap-3 border bg-surface-alt px-4 py-3 '.$cfg['border']]) }}
    role="{{ $type === 'error' ? 'alert' : 'status' }}"
>
    <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $cfg['dot'] }}" aria-hidden="true"></span>
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-bold text-ink">{{ $title }}</p>
        @endif
        <div class="text-[15px] leading-snug text-static">{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" class="ml-auto shrink-0 text-lg leading-none text-static hover:text-ink" data-dismiss aria-label="Dismiss">&times;</button>
    @endif
</div>
