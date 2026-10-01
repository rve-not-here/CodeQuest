@props(['title' => '', 'subtitle' => ''])

{{-- One eyebrow, one title, one sentence of context. The decorative
     glyph prop this used to accept was removed: it carried no
     information and was passed inconsistently across 42 call sites. --}}
<header class="page-header">
    <div class="min-w-0">
        @if ($title)
            <p class="terminal-kicker">SYSTEM 404 // LEARNING NETWORK</p>
            <h1>{{ $title }}</h1>
        @endif
        @if ($subtitle)
            <p class="page-header-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="ml-auto flex shrink-0 flex-wrap items-center justify-end gap-2">{{ $actions }}</div>
    @endisset
</header>
