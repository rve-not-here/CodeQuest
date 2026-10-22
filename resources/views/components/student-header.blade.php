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
                    @foreach ($secondaryGroups as $heading => $groupItems)
                        @if ($heading !== $secondaryLabel)
                            <p class="atlas-group-label">{{ $heading }}</p>
                        @endif
                        @foreach ($groupItems as $item)
                            <a href="{{ route($item['route']) }}" @if ($isCurrent($item)) aria-current="page" @endif><x-icon :name="$item['icon']" size="16" />{{ $item['label'] }}</a>
                        @endforeach
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
                        @foreach ($secondaryGroups as $heading => $groupItems)
                            <p class="atlas-group-label">{{ $heading }}</p>
                            @foreach ($groupItems as $item)
                                <a href="{{ route($item['route']) }}" @if ($isCurrent($item)) aria-current="page" @endif><x-icon :name="$item['icon']" size="16" />{{ $item['label'] }}</a>
                            @endforeach
                        @endforeach
                    @endif
                </nav>
            </details>
            <x-theme-toggle
                :endpoint="auth()->check() ? route('theme.update') : null"
                :stored="$themePreference ?? null"
            />
            <a href="{{ route('notifications') }}" class="atlas-notifications" aria-label="Notifications{{ $unreadNotificationCount > 0 ? ', '.$unreadNotificationCount.' unread' : '' }}">
                <x-icon name="bell" size="18" :stroke="1.75" />
                @if ($unreadNotificationCount > 0)
                    <span class="atlas-notification-count" aria-hidden="true">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>
                @endif
            </a>
            @auth
                <details class="atlas-disclosure cq-account-menu" data-shell-disclosure data-account-disclosure>
                    <summary aria-label="Open account menu"><span class="atlas-avatar" aria-hidden="true">{{ strtoupper(substr($displayName, 0, 1)) }}</span><span class="atlas-account-label">Account</span><x-icon name="chevron-down" size="14" /></summary>
                    <div class="atlas-popover atlas-account">
                        <p class="atlas-account-name">{{ $displayName }}</p>
                        <p class="atlas-group-label">{{ ucfirst($role) }} account</p>
                        <button type="button" data-dialog-open="how-codequest-works">
                            <x-icon name="book-open" size="16" />How CodeQuest works
                        </button>
                        <form id="logout-form" method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" data-dialog-open="logout-dialog" aria-label="Sign out">
                                <x-icon name="log-out" size="16" />Sign out
                            </button>
                        </form>
                    </div>
                </details>
            @endauth
        </div>
    </div>

    @auth
        <dialog class="atlas-dialog" id="logout-dialog" data-dialog aria-labelledby="logout-title" aria-describedby="logout-copy">
            <p class="atlas-dialog-kicker">Session</p>
            <h2 id="logout-title">Sign out of CodeQuest?</h2>
            <p id="logout-copy">Your saved progress, drafts and achievements stay on this account.</p>
            <div class="atlas-dialog-actions">
                <form method="dialog">
                    <button type="submit" class="btn btn-secondary" data-dialog-cancel>Stay signed in</button>
                </form>
                <button type="submit" form="logout-form" class="btn btn-primary">Sign out</button>
            </div>
        </dialog>

        <dialog class="atlas-dialog" id="how-codequest-works" data-dialog aria-labelledby="how-title">
            <p class="atlas-dialog-kicker">Field guide</p>
            <h2 id="how-title">How CodeQuest works</h2>
            <ol class="atlas-how-list">
                <li><span>Study</span><p>Read the lesson and try its examples in the practice bench.</p></li>
                <li><span>Check</span><p>Answer the Knowledge Check before the Coding Challenge opens.</p></li>
                <li><span>Practice</span><p>Write the challenge, run the preview, then submit it for server evaluation.</p></li>
                <li><span>Prove</span><p>Clear the course Boss Challenge to complete the course.</p></li>
            </ol>
            <p class="atlas-dialog-note">XP records what you earn and what you choose to spend. The Timeline and Performance Summary keep every recorded result.</p>
            <div class="atlas-dialog-actions">
                <form method="dialog">
                    <button type="submit" class="btn btn-secondary" data-dialog-cancel>Got it</button>
                </form>
            </div>
        </dialog>
    @endauth
</header>
