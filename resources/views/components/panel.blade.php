@props(['title' => '', 'actions' => null, 'code' => null])

@php
    if ($code === null && $title !== '') {
        $hash = substr(sha1($title), 0, 4);
        $prefix = strtoupper(preg_replace('/[^A-Z]/', '', substr($title, 0, 3)) ?: 'SEC');
        $code = $prefix.'-'.strtoupper($hash);
    }
@endphp

<section {{ $attributes->merge(['class' => 'panel p-4 sm:p-5']) }}>
    @if ($title || $actions || $code)
        <header class="mb-4 flex items-center justify-between gap-4 border-b border-accent-line/15 pb-3">
            <div class="min-w-0 flex items-center gap-3">
                @if ($code)
                    <span class="sort-code">{{ $code }}</span>
                @endif
                @if ($title)
                    <h2 class="panel-title">{{ $title }}</h2>
                @endif
            </div>
            @if ($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endif
        </header>
    @endif
    <div class="min-w-0">{{ $slot }}</div>
</section>
