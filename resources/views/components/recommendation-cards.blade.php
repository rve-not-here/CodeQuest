@props(['recommendations'])

{{--
    Shared recommendation card rows (US-908). The single rendering of a
    RecommendationService card — slot badge, title, student-safe subtitle,
    and CTA link — used by both the /recommendations page and the
    learning-path panel so the two surfaces can never drift apart.

    Cards annotated with accessible=false (learning-path integration only)
    keep their title and reason but render a non-actionable LOCKED state
    instead of a link: a recommendation must never point at a target the
    student cannot open. Cards without the annotation (the /recommendations
    page) render exactly as before.
--}}
<div class="divide-y divide-phosphor-dim/40">
    @foreach ($recommendations as $recommendation)
        <div class="flex items-center gap-3 py-3">
            <x-badge tone="{{ $recommendation['slot'] === 3 ? 'amber' : 'phosphor' }}">
                PRIORITY {{ $recommendation['slot'] }}
            </x-badge>
            <div class="min-w-0">
                <p class="font-body text-[15px] text-ink truncate">{{ $recommendation['title'] }}</p>
                <p class="text-xs font-bold text-phosphor-dim truncate">{{ $recommendation['subtitle'] }}</p>
                @if (! ($recommendation['accessible'] ?? true) && ($recommendation['locked_reason'] ?? null))
                    <p class="text-xs text-amber truncate">{{ $recommendation['locked_reason'] }}</p>
                @endif
            </div>
            @if ($recommendation['accessible'] ?? true)
                <a
                    href="{{ $recommendation['href'] }}"
                    class="ml-auto shrink-0 text-sm font-bold border border-phosphor-dim text-phosphor hover:text-void hover:bg-phosphor px-3 py-1 rounded-[2px]"
                >
                    {{ $recommendation['cta'] }} →
                </a>
            @else
                <span class="ml-auto shrink-0">
                    <x-badge tone="dim">LOCKED</x-badge>
                </span>
            @endif
        </div>
    @endforeach
</div>
