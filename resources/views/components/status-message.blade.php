@props(['type' => 'info', 'title' => '', 'dismissible' => false])

@php
    $map = [
        'success' => ['dot' => 'bg-[#2c9c4f]', 'text' => 'text-[#2c9c4f]'],
        'info' => ['dot' => 'bg-[#198eee]', 'text' => 'text-[#198eee]'],
        'warning' => ['dot' => 'bg-amber', 'text' => 'text-amber'],
        'error' => ['dot' => 'bg-alert', 'text' => 'text-alert'],
    ];
    $cfg = $map[$type] ?? $map['info'];
@endphp

<div
    {{ $attributes->merge(['class' => 'flex items-start gap-3 border border-phosphor-dim/70 bg-surface-alt px-4 py-3 rounded-[2px]']) }}
    role="status"
>
    <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $cfg['dot'] }}" aria-hidden="true"></span>
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-bold text-[#0a0a23]">{{ $title }}</p>
        @endif
        <div class="text-[15px] leading-snug text-[#3b3b4f]">{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" class="ml-auto text-[#8f8f9a] hover:text-[#0a0a23] shrink-0 text-lg leading-none" data-dismiss aria-label="Dismiss">&times;</button>
    @endif
</div>