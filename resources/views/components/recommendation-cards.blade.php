@props(['recommendations', 'actionable' => true, 'variant' => 'default'])

{{-- Availability is supplied by the service. Teacher usage stays informational. --}}
@if ($variant === 'evidence')
    @php($nextActionKey = $recommendations->search(fn ($recommendation) => $actionable && ($recommendation['accessible'] ?? true)))
    <ol aria-label="Recommended actions" class="atlas-action-sequence">
        @foreach ($recommendations as $key => $recommendation)
            <li @if ($key === $nextActionKey) data-next-action @endif>
                <p class="font-mono text-xs text-accent">PRIORITY {{ $recommendation['slot'] }}</p>
                <div>
                    @if ($key === $nextActionKey)
                        <p class="mb-2 text-sm text-fg-muted">Next available action</p>
                    @endif
                    <h3 class="text-xl font-semibold">{{ $recommendation['title'] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-fg-muted">{{ $recommendation['subtitle'] }}</p>
                    @if (! ($recommendation['accessible'] ?? true) && ($recommendation['locked_reason'] ?? null))
                        <p class="mt-2 text-sm text-warning">{{ $recommendation['locked_reason'] }}</p>
                    @endif
                </div>
                @if ($actionable && ($recommendation['accessible'] ?? true))
                    <a href="{{ $recommendation['href'] }}" class="btn {{ $key === $nextActionKey ? 'btn-primary' : 'btn-secondary' }}">{{ $recommendation['cta'] }} →</a>
                @elseif (($recommendation['accessible'] ?? true) === false)
                    <span class="badge badge-locked">LOCKED</span>
                @endif
            </li>
        @endforeach
    </ol>
@else
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
                    <p class="truncate text-xs {{ $variant === 'student' ? 'text-fg-muted' : 'font-bold text-fg-muted' }}">{{ $recommendation['subtitle'] }}</p>
                    @if (! ($recommendation['accessible'] ?? true) && ($recommendation['locked_reason'] ?? null))
                        <p class="truncate text-xs {{ $variant === 'student' ? 'text-warning' : 'text-warning' }}">{{ $recommendation['locked_reason'] }}</p>
                    @endif
                </div>
                @if ($actionable && ($recommendation['accessible'] ?? true))
                    <a
                        href="{{ $recommendation['href'] }}"
                        class="ml-auto shrink-0 {{ $variant === 'student' ? 'btn btn-secondary btn-sm' : 'rounded-[2px] border border-fg-muted px-3 py-1 text-sm font-bold text-accent hover:bg-accent hover:text-void' }}"
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

@endif
