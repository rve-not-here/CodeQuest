@props(['endpoint' => null, 'stored' => null])

@php
    $mode = $stored === 'dark' ? 'dark' : 'light';
@endphp
{{--
    A single icon button toggles the current mode. Its accessible name and
    tooltip state the mode the next press will apply.
--}}
<div class="atlas-theme-toggle">
    <button
        type="button"
        class="atlas-theme-toggle-button"
        data-theme-toggle
        @if ($endpoint !== null) data-theme-endpoint="{{ $endpoint }}" @endif
        @if ($stored !== null) data-theme-server="{{ $stored }}" @endif
        aria-label="Switch to {{ $mode === 'dark' ? 'light' : 'dark' }} mode"
        title="Switch to {{ $mode === 'dark' ? 'light' : 'dark' }} mode"
    >
        <x-icon name="sun" size="16" data-theme-icon="light" />
        <x-icon name="moon" size="16" data-theme-icon="dark" />
    </button>
    <span class="sr-only" role="status" data-theme-announcer></span>
</div>
