@props(['type' => 'info', 'title' => ''])

@php
    $tone = in_array($type, ['success', 'info', 'warning', 'error'], true) ? $type : 'info';
@endphp

<div class="student-toast student-toast--{{ $tone }}" role="{{ $tone === 'error' ? 'alert' : 'status' }}" aria-atomic="true">
    <span class="student-toast-dot" aria-hidden="true"></span>
    <div class="student-toast-copy">
        @if ($title)
            <strong>{{ $title }}</strong>
        @endif
        <span>{{ $slot }}</span>
    </div>
    <button type="button" class="student-toast-close" data-toast-dismiss aria-label="Dismiss {{ $title ?: 'message' }}">&times;</button>
</div>
