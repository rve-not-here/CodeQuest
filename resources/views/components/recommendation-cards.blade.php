@props(['recommendations', 'actionable' => true, 'variant' => 'default'])

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

    US-909 teacher mode (actionable=false) renders title and reason with no
    anchor, href, form, or navigation target of any kind: the teacher
    surface is informational only and cannot forge student actions.
--}}
<div class="{{ $variant === 'student' ? 'divide-y divide-line' : 'divide-y divide-phosphor-dim/40' }}">
    @foreach ($recommendations as $recommendation)
        <div class="flex flex-wrap items-center gap-3 py-3">
            @if ($variant === 'student')
                <span class="badge {{ $recommendation['slot'] === 3 ? 'badge-warning' : 'badge-accent' }}">PRIORITY {{ $recommendation['slot'] }}</span>
            @else
                <x-badge tone="{{ $recommendation['slot'] === 3 ? 'amber' : 'phosphor' }}">PRIORITY {{ $recommendation['slot'] }}</x-badge>
            @endif
            <div class="min-w-0">
                <p class="truncate text-[15px] {{ $variant === 'student' ? 'text-fg' : 'font-body text-ink' }}">{{ $recommendation['title'] }}</p>
                <p class="truncate text-xs {{ $variant === 'student' ? 'text-fg-muted' : 'font-bold text-phosphor-dim' }}">{{ $recommendation['subtitle'] }}</p>
                @if (! ($recommendation['accessible'] ?? true) && ($recommendation['locked_reason'] ?? null))
                    <p class="truncate text-xs {{ $variant === 'student' ? 'text-warning' : 'text-amber' }}">{{ $recommendation['locked_reason'] }}</p>
                @endif
            </div>
            @if ($actionable && ($recommendation['accessible'] ?? true))
                <a
                    href="{{ $recommendation['href'] }}"
                    class="ml-auto shrink-0 {{ $variant === 'student' ? 'btn btn-secondary btn-sm' : 'rounded-[2px] border border-phosphor-dim px-3 py-1 text-sm font-bold text-phosphor hover:bg-phosphor hover:text-void' }}"
                >
                    {{ $recommendation['cta'] }} →
                </a>
            @elseif (($recommendation['accessible'] ?? true) === false)
                <span class="ml-auto shrink-0">
                    @if ($variant === 'student')
                        <span class="badge badge-locked">LOCKED</span>
                    @else
                        <x-badge tone="dim">LOCKED</x-badge>
                    @endif
                </span>
            @endif
        </div>
    @endforeach
</div>
