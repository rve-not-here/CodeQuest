@props(['title' => '', 'subtitle' => '', 'icon' => null])

<header class="page-header">
    <div class="min-w-0">
        @if ($title)
            <p class="terminal-kicker">{{ auth()->user()?->role === 'admin' ? 'SYSTEM 404 // MANAGEMENT' : (auth()->user()?->role === 'teacher' ? 'SYSTEM 404 // CLASSROOM MONITORING' : 'SYSTEM 404 // LEARNING NETWORK') }}</p>
            <h1 class="mt-2 font-sans text-2xl font-semibold tracking-[-0.025em] text-ink md:text-3xl">
                @if ($icon)<span aria-hidden="true">{{ $icon }} </span>@endif{{ $title }}
            </h1>
        @endif
        @if ($subtitle)
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-static md:text-base">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="ml-auto flex shrink-0 flex-wrap items-center justify-end gap-2">{{ $actions }}</div>
    @endisset
</header>
