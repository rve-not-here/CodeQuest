@props(['endpoint' => null, 'stored' => null])

@php
    $mode = $stored === 'dark' ? 'dark' : 'light';
    $next = $mode === 'dark' ? 'light' : 'dark';
    $label = ucfirst($mode);
@endphp
<div class="atlas-theme-toggle">
    <button
        type="button"
        class="atlas-theme-toggle-button"
        data-theme-toggle
        @if ($endpoint !== null) data-theme-endpoint="{{ $endpoint }}" @endif
        @if ($stored !== null) data-theme-server="{{ $stored }}" @endif
        aria-label="Theme: {{ $label }}. Switch to {{ $next }}."
        title="Theme: {{ $label }}"
    >
        <x-icon name="sun" size="18" data-theme-icon="light" />
        <x-icon name="moon" size="18" data-theme-icon="dark" />
    </button>
    <span class="sr-only" role="status" data-theme-announcer></span>
</div>
