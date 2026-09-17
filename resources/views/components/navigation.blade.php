@props(['items' => [], 'active' => null])

@php
    $active = $active ?: request()->route()?->getName();
    $position = 0;
@endphp

<nav class="flex flex-col gap-1" aria-label="Primary">
    @foreach ($items as $item)
    @if (isset($item['heading']))
        <p class="cq-nav-heading">
            {{ $item['heading'] }}
        </p>
    @else
        @php
            $position++;
            $href = !empty($item['route']) ? route($item['route']) : ($item['url'] ?? '#');
            $isActive = $active === ($item['route'] ?? null);
        @endphp

        <a
            href="{{ $href }}"
            class="cq-side-link {{ $isActive ? 'cq-side-link--active' : '' }}"
            aria-current="{{ $isActive ? 'page' : 'false' }}"
        >
            <span class="navigation-index" aria-hidden="true">{{ str_pad((string) $position, 2, '0', STR_PAD_LEFT) }}</span>
            <span class="truncate">{{ $item['label'] }}</span>
        </a>
    @endif
    @endforeach
</nav>
