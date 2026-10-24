@props(['endpoint' => null, 'stored' => null])

@php
    // Dark is the product's default surface; an account or device choice
    // overrides it. The two options name the mode each one applies.
    $mode = $stored === 'light' ? 'light' : 'dark';
@endphp
{{--
    A two-option setter, not a toggle. Each option names the mode it applies
    and the option in force is pressed (aria-pressed). An icon standing for
    "the other mode" is ambiguous, so there is nothing to interpret here.
--}}
<div class="atlas-theme-toggle" role="group" aria-label="Colour theme">
    <button
        type="button"
        class="atlas-theme-option"
        data-theme-toggle
        data-theme-set="light"
        aria-pressed="{{ $mode === 'light' ? 'true' : 'false' }}"
        aria-label="Light mode"
        title="Light mode"
        @if ($endpoint !== null) data-theme-endpoint="{{ $endpoint }}" @endif
        @if ($stored !== null) data-theme-server="{{ $stored }}" @endif
    >
        <x-icon name="sun" size="13" />
        <span class="atlas-theme-label">Light</span>
    </button>
    <button
        type="button"
        class="atlas-theme-option"
        data-theme-toggle
        data-theme-set="dark"
        aria-pressed="{{ $mode === 'dark' ? 'true' : 'false' }}"
        aria-label="Dark mode"
        title="Dark mode"
        @if ($endpoint !== null) data-theme-endpoint="{{ $endpoint }}" @endif
        @if ($stored !== null) data-theme-server="{{ $stored }}" @endif
    >
        <x-icon name="moon" size="13" />
        <span class="atlas-theme-label">Dark</span>
    </button>
</div>
<span class="sr-only" role="status" data-theme-announcer></span>
