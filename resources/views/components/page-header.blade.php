@props(['title' => '', 'subtitle' => '', 'icon' => null])

<header class="page-header">
    <div class="min-w-0">
        @if ($title)
            {{-- No kicker above the heading: the craft floor bans the device outright. The
                 heading carries its own weight, and the role is already stated in the shell. --}}
            <h1 class="font-sans text-2xl font-semibold tracking-[-0.025em] text-ink md:text-3xl">
                @if ($icon)<x-workspace-icon :name="$icon" class="page-header-icon" />@endif{{ $title }}
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
