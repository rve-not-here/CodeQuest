@php
    $authUser = auth()->user();
    $role = $role ?? ($authUser?->role ?? 'student');
    $displayName = $authUser?->username ?? $profileName ?? 'OPERATOR';

    $studentItems = [
        ['heading' => 'Journey'],
        ['route' => 'dashboard', 'label' => 'Dashboard'],
        ['route' => 'learning-path', 'label' => 'Learning Path'],
        ['heading' => 'Learning record'],
        ['route' => 'progress', 'label' => 'Course Progress'],
        ['route' => 'section-progress', 'label' => 'Section Progress'],
        ['route' => 'timeline', 'label' => 'Timeline'],
        ['route' => 'xp-ledger', 'label' => 'XP Ledger'],
        ['route' => 'competency', 'label' => 'Competency'],
        ['heading' => 'Support'],
        ['route' => 'recommendations', 'label' => 'Recommendations'],
        ['route' => 'achievements', 'label' => 'Achievements'],
        ['route' => 'assessments', 'label' => 'Boss Challenges'],
        ['route' => 'notifications', 'label' => 'Notifications'],
        ['heading' => 'Reference'],
        ['route' => 'missions', 'label' => 'Challenge Index'],
    ];

    $instructorItems = [
        ['route' => 'dashboard', 'label' => 'Dashboard'],
        ['route' => 'students', 'label' => 'Students'],
        ['route' => 'classrooms', 'label' => 'My Classrooms'],
        ['heading' => 'System'],
        ['route' => 'assessments', 'label' => 'Assessments'],
        ['route' => 'competency', 'label' => 'Competency'],
        ['route' => 'activity', 'label' => 'Learning Activity'],
        ['route' => 'course-analytics', 'label' => 'Course Analytics'],
        ['route' => 'needs-attention', 'label' => 'Needs Attention'],
        ['route' => 'notifications', 'label' => 'Notifications'],
    ];

    $adminItems = [
        ['route' => 'admin.dashboard', 'label' => 'Admin Console'],
        ['route' => 'admin.users', 'label' => 'Users'],
        ['route' => 'admin.courses', 'label' => 'Courses'],
        ['route' => 'admin.classrooms', 'label' => 'Classrooms'],
        ['route' => 'admin.announcements', 'label' => 'Announcements'],
        ['route' => 'admin.activity', 'label' => 'Audit Trail'],
        ['route' => 'admin.analytics', 'label' => 'System Analytics'],
        ['route' => 'admin.system', 'label' => 'System Status'],
        ['route' => 'notifications', 'label' => 'Notifications'],
    ];

    $items = match ($role) {
        'student' => $studentItems,
        'admin' => $adminItems,
        default => $instructorItems,
    };

    $activeRoute = request()->route()?->getName();
    $topItems = array_values(array_filter(
        $items,
        fn ($item) => ! isset($item['heading'])
            && ($role !== 'student' || in_array($item['route'] ?? null, ['dashboard', 'learning-path', 'notifications'], true)),
    ));
    $standalone = $standalone ?? false;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CodeQuest') · CodeQuest</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-ink font-body min-h-screen">
    <div class="flex min-h-screen flex-col">
        @unless ($standalone)
        <header class="cq-topnav sticky top-0 z-40" data-role="{{ $role }}" data-drawer-content>
            <div class="cq-topnav-inner mx-auto flex max-w-[1536px] items-center gap-4 px-4 md:px-6">
                @if ($role !== 'student' && ! ($workspace ?? false))
                <button
                    id="cq-menu-trigger"
                    type="button"
                    class="cq-icon-button cq-menu-trigger -ml-1"
                    data-open
                    aria-label="Open navigation menu"
                    aria-controls="cq-sidebar"
                    aria-expanded="false"
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true">
                        <path d="M3 6h18M3 12h18M3 18h18" />
                    </svg>
                </button>
                @endif

                <a href="{{ route('dashboard') }}" class="cq-brand shrink-0" aria-label="CodeQuest home">
                    <span class="cq-brand-mark" aria-hidden="true">404</span>
                    <span>
                        <span class="cq-brand-name">CodeQuest</span>
                        <span class="cq-brand-subtitle">Learning terminal</span>
                    </span>
                </a>

                <nav class="cq-primary-nav" aria-label="Primary">
                    @foreach ($topItems as $item)
                        @php
                            $href = ! empty($item['route']) ? route($item['route']) : ($item['url'] ?? '#');
                            $isActive = $activeRoute === ($item['route'] ?? null)
                                || ($role === 'student'
                                    && ($item['route'] ?? null) === 'learning-path'
                                    && in_array($activeRoute, [
                                        'mission.show',
                                        'mission.challenge',
                                        'knowledge-check.show',
                                    ], true));
                        @endphp
                        <a
                            href="{{ $href }}"
                            class="cq-navlink {{ $isActive ? 'cq-navlink--active' : '' }}"
                            aria-current="{{ $isActive ? 'page' : 'false' }}"
                        >{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                <div class="cq-account-area ml-auto flex items-center gap-2 sm:gap-4">
                    @if ($role !== 'student')
                    <a href="{{ route('notifications') }}" class="cq-navbar-link" aria-label="Open notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true">
                            <path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6" />
                            <path d="M9 19a3 3 0 0 0 6 0" />
                        </svg>
                        <span class="hidden sm:inline">Notifications</span>
                    </a>
                    @endif
                    @auth
                        @if ($role === 'student')
                            <details class="cq-account-menu">
                                <summary class="cq-account-trigger" aria-label="Open account menu">
                                    <span class="cq-avatar" aria-hidden="true">
                                        {{ strtoupper(substr($displayName, 0, 1)) }}
                                    </span>
                                    <span class="cq-account-label">Account</span>
                                </summary>
                                <div class="cq-account-popover">
                                    <p class="truncate font-semibold text-ink">{{ $displayName }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.1em] text-static">Student account</p>
                                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                                        @csrf
                                        <button type="submit" class="cq-account-action">Sign out</button>
                                    </form>
                                </div>
                            </details>
                        @else
                            <div class="hidden sm:flex items-center gap-2 text-ink text-sm min-w-0">
                                <span class="cq-avatar">
                                    {{ strtoupper(substr($displayName, 0, 1)) }}
                                </span>
                                <span class="truncate">{{ $displayName }}</span>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="cq-navbar-link">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                        <path d="M16 17l5-5-5-5M21 12H9" />
                                    </svg>
                                    <span class="hidden md:inline">Sign out</span>
                                </button>
                            </form>
                        @endif
                    @endauth
                </div>
            </div>
        </header>
        @endunless

        <div class="flex flex-1 min-w-0">
            {{-- Mobile drawer --}}
            @if (! $standalone && ! ($workspace ?? false) && $role !== 'student')
            <div
                id="cq-backdrop"
                data-close
                class="fixed inset-0 z-40 hidden bg-black/75 lg:hidden"
                aria-hidden="true"
            ></div>
            <aside
                id="cq-sidebar"
                class="cq-sidebar fixed inset-y-0 left-0 z-50 flex w-[min(20rem,88vw)] -translate-x-full flex-col transition-transform duration-200 lg:hidden"
                role="dialog"
                aria-label="Navigation menu"
                aria-modal="true"
                aria-hidden="true"
                tabindex="-1"
                inert
            >
                <div class="flex items-center justify-between gap-2 border-b border-phosphor/20 px-5 py-5">
                    <a href="{{ route('dashboard') }}" class="cq-brand" aria-label="CodeQuest home">
                        <span class="cq-brand-mark" aria-hidden="true">404</span>
                        <span class="cq-brand-name">CodeQuest</span>
                    </a>
                    <button type="button" data-close data-drawer-initial-focus class="cq-icon-button" aria-label="Close menu">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true">
                            <path d="M5 5l14 14M19 5L5 19" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-3 py-4">
                    <x-navigation :items="$items" />
                </div>
                <div class="mt-auto flex items-center gap-3 border-t border-phosphor/20 px-6 py-4">
                    <span class="cq-avatar">
                        {{ strtoupper(substr($displayName, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm text-ink">{{ $displayName }}</p>
                        <p class="text-xs uppercase tracking-[0.12em] text-static">{{ $role }} access</p>
                    </div>
                </div>
            </aside>
            @endif

            <main class="min-w-0 flex-1 {{ $standalone ? 'w-full' : ($workspace ?? false ? 'w-full' : 'w-full p-4 sm:p-5 md:p-7') }}" data-drawer-content>
                <div class="{{ $standalone || ($workspace ?? false) ? '' : 'mx-auto w-full max-w-[1180px]' }}">
                    @yield('content')
                </div>
            </main>
        </div>

        @unless ($standalone || ($workspace ?? false))
        <footer class="manual-footer" data-drawer-content>
            <p>CODEQUEST // SYSTEM 404 LEARNING NETWORK</p>
            <p>STATUS: ONLINE · ACCESS: {{ strtoupper($role) }}</p>
        </footer>
        @endunless
    </div>

    @stack('scripts')
</body>
</html>
