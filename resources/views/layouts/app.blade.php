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
    $primaryRoutes = match ($role) {
        'student' => ['dashboard', 'learning-path', 'notifications'],
        'admin' => ['admin.dashboard', 'admin.users', 'admin.courses'],
        default => ['dashboard', 'students', 'classrooms'],
    };
    $topItems = array_values(array_filter($items, fn ($item) => in_array($item['route'] ?? null, $primaryRoutes, true)));
    $accountGroups = [];
    $pendingHeading = null;
    foreach ($items as $item) {
        if (isset($item['heading'])) {
            $pendingHeading = $item;
        } elseif (! in_array($item['route'] ?? null, $primaryRoutes, true)) {
            if ($pendingHeading !== null) {
                $accountGroups[] = $pendingHeading;
                $pendingHeading = null;
            }
            $accountGroups[] = $item;
        }
    }
    $accountHref = fn ($item) => ! empty($item['route']) ? route($item['route']) : ($item['url'] ?? '#');
    $accountActive = fn ($item) => $activeRoute === ($item['route'] ?? null);
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
                            @if ($isActive) aria-current="page" @endif
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
                            <details class="cq-account-menu" id="cq-account-menu">
                                <summary class="cq-account-trigger" aria-label="Open account menu" data-account-trigger>
                                    <span class="cq-avatar" aria-hidden="true">
                                        {{ strtoupper(substr($displayName, 0, 1)) }}
                                    </span>
                                    <span class="cq-account-label">Account</span>
                                </summary>
                                <div class="cq-account-popover">
                                    <div class="border-b border-phosphor/15 px-3 py-2.5">
                                        <p class="truncate font-semibold text-ink">{{ $displayName }}</p>
                                        <p class="mt-0.5 text-xs text-static">{{ ucfirst($role) }} account</p>
                                    </div>

                                    <div class="cq-account-scroll">
                                        @foreach ($accountGroups as $item)
                                            @if (isset($item['heading']))
                                                <p class="cq-account-group">{{ $item['heading'] }}</p>
                                            @else
                                                <a
                                                    href="{{ $accountHref($item) }}"
                                                    class="cq-account-link"
                                                    @if ($accountActive($item)) aria-current="page" @endif
                                                >{{ $item['label'] }}</a>
                                            @endif
                                        @endforeach
                                    </div>

                                    <div class="cq-account-footer">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="cq-account-action">Sign out</button>
                                        </form>
                                    </div>
                                </div>
                            </details>
                    @endauth
                </div>
            </div>
        </header>
        @endunless

        <div class="flex flex-1 min-w-0">
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
