<svg {{ $attributes->class(['icon']) }} xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round" @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif>
    {!! $body !!}
</svg>
