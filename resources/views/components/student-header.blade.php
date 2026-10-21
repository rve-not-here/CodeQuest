@php
    $isCurrent = fn ($item) => $activeRoute === $item['route']
        || str_starts_with((string) $activeRoute, $item['route'].'.')
        || ($item['route'] === 'learning-path' && in_array($activeRoute, ['mission.show', 'mission.experiment', 'mission.challenge', 'knowledge-check.show'], true))
        || ($item['route'] === 'assessments' && $activeRoute === 'assessment.show');
@endphp
<a href="#main" class="atlas-skip">Skip to content</a>
<header class="atlas-shell" data-role="{{ $role }}" data-atlas-shell>
    <div class="atlas-shell-inner">
        <a href="{{ route($homeRoute) }}" class="atlas-brand" aria-label="CodeQuest home">
            <span class="atlas-mark" aria-hidden="true">404</span>
            <span>CodeQuest</span>
        </a>
        <nav aria-label="Primary" class="atlas-primary">
            @foreach ($topItems as $item)
                <a href="{{ route($item['route']) }}" @if ($isCurrent($item)) aria-current="page" @endif>@if ($item['primaryIcon'] ?? true)<x-icon :name="$item['icon']" size="14" />@endif{{ $item['label'] }}</a>
            @endforeach
        </nav>
        @if ($secondaryItems)
            <details class="atlas-disclosure atlas-secondary" data-shell-disclosure>
                <summary>{{ $secondaryLabel }} <x-icon name="chevron-down" size="14" /></summary>
                <nav class="atlas-popover" aria-label="{{ $secondaryLabel }}">
                    @foreach ($secondaryItems as $item)
                        <a href="{{ route($item['route']) }}" @if ($isCurrent($item)) aria-current="page" @endif><x-icon :name="$item['icon']" size="16" />{{ $item['label'] }}</a>
                    @endforeach
                </nav>
            </details>
        @endif
        <div class="atlas-shell-actions">
            @if ($role === 'student')
                @isset($totalXp)
                    <a class="atlas-xp" href="{{ route('xp-ledger') }}" aria-label="XP balance {{ $totalXp }}. Open XP ledger">{{ number_format($totalXp) }} <span>XP</span></a>
                @endisset
            @endif
            <details class="atlas-disclosure atlas-mobile" data-shell-disclosure data-navigation-disclosure>
                <summary aria-label="Open navigation menu"><span class="atlas-mobile-label">Menu</span> <x-icon name="menu" size="18" /></summary>
                <nav class="atlas-popover atlas-mobile-links" aria-label="Mobile navigation">
                    @foreach ($topItems as $item)
                        <a href="{{ route($item['route']) }}" @if ($isCurrent($item)) aria-current="page" @endif><x-icon :name="$item['icon']" size="16" />{{ $item['label'] }}</a>
                    @endforeach
                    @if ($secondaryItems)
                        <p class="atlas-group-label">{{ $secondaryLabel }}</p>
                        @foreach ($secondaryItems as $item)
                            <a href="{{ route($item['route']) }}" @if ($isCurrent($item)) aria-current="page" @endif><x-icon :name="$item['icon']" size="16" />{{ $item['label'] }}</a>
                        @endforeach
                    @endif
                </nav>
            </details>
            <x-theme-toggle
                :endpoint="auth()->check() ? route('theme.update') : null"
                :stored="$themePreference ?? null"
            />
            <a href="{{ route('notifications') }}" class="atlas-notifications" aria-label="Notifications">
                <x-icon name="bell" size="18" :stroke="1.75" />
            </a>
            @auth
                <details class="atlas-disclosure cq-account-menu" data-shell-disclosure data-account-disclosure>
                    <summary aria-label="Open account menu"><span class="atlas-avatar" aria-hidden="true">{{ strtoupper(substr($displayName, 0, 1)) }}</span><span class="atlas-account-label">Account</span><x-icon name="chevron-down" size="14" /></summary>
                    <div class="atlas-popover atlas-account">
                        <p class="atlas-account-name">{{ $displayName }}</p>
                        <p class="atlas-group-label">{{ ucfirst($role) }} account</p>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" aria-label="Sign out"><x-icon name="log-out" size="16" />Sign out</button>
                        </form>
                    </div>
                </details>
            @endauth
        </div>
    </div>
</header>
