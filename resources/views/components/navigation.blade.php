@props(['items' => [], 'active' => null])

@php
    $active = $active ?: request()->route()?->getName();
@endphp

<nav class="flex flex-col gap-1" aria-label="Primary">
    @foreach ($items as $item)
    @if (isset($item['heading']))
        <p class="mt-6 mb-1 px-3 text-xs font-bold uppercase tracking-wide text-white/50">
            {{ $item['heading'] }}
        </p>
    @else
        @php
            $href = !empty($item['route']) ? route($item['route']) : ($item['url'] ?? '#');
            $isActive = $active === ($item['route'] ?? null);
        @endphp

        <a
            href="{{ $href }}"
            class="flex items-center gap-3 px-3 py-2.5 text-[15px] rounded-[2px]
                   {{ $isActive ? 'bg-white/10 text-white' : 'text-white/85 hover:text-[#69b6f5] hover:bg-white/5' }}"
            aria-current="{{ $isActive ? 'page' : 'false' }}"
        >
            <span class="truncate">{{ $item['label'] }}</span>
        </a>
    @endif
    @endforeach
</nav>