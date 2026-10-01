@props(['type' => 'info', 'title' => '', 'dismissible' => false])

@php
    $map = [
        'success' => ['dot' => 'bg-phosphor', 'text' => 'text-phosphor'],
        'info' => ['dot' => 'bg-cyan', 'text' => 'text-cyan'],
        'warning' => ['dot' => 'bg-amber', 'text' => 'text-amber'],
        'error' => ['dot' => 'bg-alert', 'text' => 'text-alert'],
    ];
    $cfg = $map[$type] ?? $map['info'];
@endphp

{{-- The message type is carried by the title text and the rule under
     it, not by the dot alone: nothing here depends on telling green
     from red. --}}
<div
    {{ $attributes->merge(['class' => 'border border-phosphor/20 bg-surface-alt px-4 py-3']) }}
    role="status"
>
    <div class="flex items-start gap-3">
        <span class="mt-[0.45rem] h-2 w-2 shrink-0 {{ $cfg['dot'] }}" aria-hidden="true"></span>
        <div class="min-w-0 flex-1">
            @if ($title)
                <p class="font-semibold {{ $cfg['text'] }}">{{ $title }}</p>
            @endif
            <div class="mt-0.5 text-[0.9375rem] leading-relaxed text-static">{{ $slot }}</div>
        </div>
        @if ($dismissible)
            <button type="button" class="ml-auto shrink-0 px-1 text-xl leading-none text-static hover:text-ink" data-dismiss aria-label="Dismiss">&times;</button>
        @endif
    </div>
</div>
