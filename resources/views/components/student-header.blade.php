@php
    $isCurrent = fn ($item) => $activeRoute === $item['route']
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
                <a href="{{ route($item['route']) }}" @if ($isCurrent($item)) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
        </nav>
        @if ($secondaryItems)
            <details class="atlas-disclosure atlas-secondary" data-shell-disclosure>
                <summary>{{ $secondaryLabel }} <span aria-hidden="true">⌄</span></summary>
                <nav class="atlas-popover" aria-label="{{ $secondaryLabel }}">
                    @foreach ($secondaryItems as $item)
                        <a href="{{ route($item['route']) }}" @if ($isCurrent($item)) aria-current="page" @endif>{{ $item['label'] }}</a>
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
                <summary aria-label="Open navigation menu">Menu <span aria-hidden="true">≡</span></summary>
                <nav class="atlas-popover atlas-mobile-links" aria-label="Mobile navigation">
                    @foreach ($topItems as $item)
                        <a href="{{ route($item['route']) }}" @if ($isCurrent($item)) aria-current="page" @endif>{{ $item['label'] }}</a>
                    @endforeach
                    @if ($secondaryItems)
                        <p class="atlas-group-label">{{ $secondaryLabel }}</p>
                        @foreach ($secondaryItems as $item)
                            <a href="{{ route($item['route']) }}" @if ($isCurrent($item)) aria-current="page" @endif>{{ $item['label'] }}</a>
                        @endforeach
                    @endif
                </nav>
            </details>
            <a href="{{ route('notifications') }}" class="atlas-notifications" aria-label="Notifications">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6M9 19a3 3 0 0 0 6 0" /></svg>
            </a>
            @auth
                <details class="atlas-disclosure cq-account-menu" data-shell-disclosure data-account-disclosure>
                    <summary aria-label="Open account menu"><span class="atlas-avatar" aria-hidden="true">{{ strtoupper(substr($displayName, 0, 1)) }}</span><span class="atlas-account-label">Account</span><span aria-hidden="true">⌄</span></summary>
                    <div class="atlas-popover atlas-account">
                        <p class="atlas-account-name">{{ $displayName }}</p>
                        <p class="atlas-group-label">{{ ucfirst($role) }} account</p>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" aria-label="Sign out">Sign out</button>
                        </form>
                    </div>
                </details>
            @endauth
        </div>
    </div>
</header>
