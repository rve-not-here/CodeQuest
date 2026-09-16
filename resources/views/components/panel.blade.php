@props(['title' => '', 'actions' => null])

<section {{ $attributes->merge(['class' => 'panel p-4']) }}>
    @if ($title || $actions)
        <header class="flex items-center justify-between gap-4 mb-4 pb-3 border-b border-phosphor-dim/60">
            @if ($title)
                <h2 class="panel-title">{{ $title }}</h2>
            @endif
            @if ($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endif
        </header>
    @endif
    <div class="min-w-0">{{ $slot }}</div>
</section>
