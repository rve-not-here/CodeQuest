@php
    $authUser = auth()->user();
    $role = $role ?? ($authUser?->role ?? 'student');
    $displayName = $authUser?->username ?? $profileName ?? 'OPERATOR';

    $studentItems = [
        ['route' => 'dashboard', 'label' => 'Dashboard'],
        ['route' => 'learning-path', 'label' => 'Learning Path'],
        ['route' => 'progress', 'label' => 'Course Progress'],
        ['route' => 'section-progress', 'label' => 'Section Progress'],
        ['route' => 'missions', 'label' => 'Missions'],
        ['heading' => 'System'],
        ['route' => 'assessments', 'label' => 'Assessments'],
        ['route' => 'timeline', 'label' => 'Timeline'],
        ['route' => 'xp-ledger', 'label' => 'XP Ledger'],
        ['route' => 'competency', 'label' => 'Competency'],
        ['route' => 'recommendations', 'label' => 'Recommendations'],
        ['route' => 'achievements', 'label' => 'Achievements'],
        ['route' => 'notifications', 'label' => 'Notifications'],
    ];

    $instructorItems = [
        ['route' => 'dashboard', 'label' => 'Dashboard'],
        ['route' => 'students', 'label' => 'Students'],
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
    $topItems = array_values(array_filter($items, fn ($item) => ! isset($item['heading'])));
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
        {{-- Top navigation bar — freeCodeCamp shell --}}
        <header class="cq-topnav sticky top-0 z-40 shadow-[0_1px_3px_rgba(10,10,35,0.35)]">
            <div class="max-w-[1360px] mx-auto flex h-16 items-center gap-4 px-4 md:px-6">
                <button
                    type="button"
                    class="lg:hidden -ml-1 text-white/90 hover:text-white inline-flex items-center justify-center w-10 h-10 shrink-0"
                    data-open
                    aria-label="Open navigation menu"
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true">
                        <path d="M3 6h18M3 12h18M3 18h18" />
                    </svg>
                </button>

                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 shrink-0" aria-label="CodeQuest home">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="#198eee" aria-hidden="true">
                        <path d="M13 1 L4 14 h5 L9 23 L19 9 h-5 Z" />
                    </svg>
                    <span class="font-display text-xl font-bold tracking-tight text-white">CodeQuest</span>
                </a>

                <nav class="hidden lg:flex items-center gap-1" aria-label="Primary">
                    @foreach ($topItems as $item)
                        @php
                            $href = ! empty($item['route']) ? route($item['route']) : ($item['url'] ?? '#');
                            $isActive = $activeRoute === ($item['route'] ?? null);
                        @endphp
                        <a
                            href="{{ $href }}"
                            class="cq-navlink {{ $isActive ? 'cq-navlink--active' : '' }}"
                            aria-current="{{ $isActive ? 'page' : 'false' }}"
                        >{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                <div class="ml-auto flex items-center gap-4">
                    <a href="{{ route('notifications') }}" class="cq-navbar-link" aria-label="Notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true">
                            <path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6" />
                            <path d="M9 19a3 3 0 0 0 6 0" />
                        </svg>
                        <span class="hidden sm:inline">Notifications</span>
                    </a>
                    @auth
                        <div class="hidden sm:flex items-center gap-2 text-white/90 text-sm min-w-0">
                            <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#198eee] text-white font-display font-bold text-sm shrink-0">
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
                    @endauth
                </div>
            </div>
        </header>
        @endunless

        <div class="flex flex-1 min-w-0">
            {{-- Mobile drawer --}}
            @unless ($standalone || ($workspace ?? false))
            <div
                id="cq-backdrop"
                data-close
                class="fixed inset-0 z-40 hidden bg-[#0a0a23]/60 lg:hidden"
                aria-hidden="true"
            ></div>
            <aside
                id="cq-sidebar"
                class="cq-topnav fixed inset-y-0 left-0 z-50 w-80 -translate-x-full transition-transform duration-200 lg:hidden flex flex-col"
                aria-label="Sidebar"
            >
                <div class="flex items-center justify-between gap-2 px-5 py-5 border-b border-white/10">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5" aria-label="CodeQuest home">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="#198eee" aria-hidden="true">
                            <path d="M13 1 L4 14 h5 L9 23 L19 9 h-5 Z" />
                        </svg>
                        <span class="font-display text-lg font-bold tracking-tight text-white">CodeQuest</span>
                    </a>
                    <button type="button" data-close class="text-white/80 hover:text-white" aria-label="Close menu">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true">
                            <path d="M5 5l14 14M19 5L5 19" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-3 py-4">
                    <x-navigation :items="$items" />
                </div>
                <div class="border-t border-white/10 px-6 py-4 flex items-center gap-3">
                    <span class="w-9 h-9 flex items-center justify-center rounded-full bg-[#198eee] text-white font-display font-bold shrink-0">
                        {{ strtoupper(substr($displayName, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm text-white truncate">{{ $displayName }}</p>
                        <p class="text-xs text-white/60 capitalize">{{ $role }}</p>
                    </div>
                </div>
            </aside>
            @endunless

            <main class="flex-1 min-w-0 {{ $standalone ? 'w-full' : ($workspace ?? false ? 'w-full' : 'p-4 md:p-6 w-full max-w-[1360px] mx-auto') }}">
                @yield('content')
            </main>
        </div>

        @unless ($standalone || ($workspace ?? false))
        <footer class="manual-footer">
            <p>CodeQuest · Education for a more functional tomorrow.</p>
            <p>System 404 · Students, teachers, builders</p>
        </footer>
        @endunless
    </div>

    @stack('scripts')
</body>
</html>