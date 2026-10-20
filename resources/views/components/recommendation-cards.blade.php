@props(['recommendations', 'actionable' => true])

{{-- Availability is supplied by the service. Teacher usage stays informational. --}}
@php($nextActionKey = $recommendations->search(fn ($recommendation) => $actionable && ($recommendation['accessible'] ?? true)))
<ol aria-label="Recommended actions" class="atlas-recommendations">
    @foreach ($recommendations as $key => $recommendation)
        @php($accessible = $recommendation['accessible'] ?? true)
        <li class="atlas-recommendation" @if ($key === $nextActionKey) data-next-action @endif>
            <span class="atlas-recommendation-slot">Priority {{ $recommendation['slot'] }}</span>
            <div class="min-w-0">
                @if ($key === $nextActionKey)
                    <p class="atlas-recommendation-note">Next available action</p>
                @endif
                <h3 class="atlas-recommendation-title">{{ $recommendation['title'] }}</h3>
                <p class="atlas-recommendation-copy">{{ $recommendation['subtitle'] }}</p>
                @if (! $accessible && ($recommendation['locked_reason'] ?? null))
                    <p class="atlas-recommendation-locked">{{ $recommendation['locked_reason'] }}</p>
                @endif
            </div>
            <div class="atlas-recommendation-action">
                @if ($actionable && $accessible)
                    <a href="{{ $recommendation['href'] }}" class="btn {{ $key === $nextActionKey ? 'btn-primary' : 'btn-secondary' }}">{{ $recommendation['cta'] }} →</a>
                @elseif (! $accessible)
                    <span class="atlas-stamp atlas-stamp--ink"><x-icon name="lock" size="12" />Locked</span>
                @endif
            </div>
        </li>
    @endforeach
</ol>
