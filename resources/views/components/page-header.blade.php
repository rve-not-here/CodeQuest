@props(['title' => '', 'subtitle' => ''])

<header class="mb-6 flex items-start gap-4 border-b border-phosphor-dim pb-4">
    <div class="min-w-0">
        @if ($title)
            <h1 class="font-display text-2xl md:text-3xl font-bold tracking-tight text-[#0a0a23]">
                {{ $title }}
            </h1>
        @endif
        @if ($subtitle)
            <p class="mt-1 text-base text-[#3b3b4f] max-w-3xl">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="ml-auto flex items-center gap-2 shrink-0">{{ $actions }}</div>
    @endisset
</header>