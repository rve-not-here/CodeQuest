@php
    $authUser = auth()->user();
    $themeState = $authUser !== null
        ? app(\App\Services\ThemePreferenceService::class)->stateFor($authUser)
        : null;
    // The shell must still render when the database is unreachable (the
    // admin system status page has to report that failure). The inbox badge is
    // decorative, so a failed count falls back to zero instead of a 500.
    $unreadNotificationCount = 0;
    if ($authUser !== null) {
        try {
            $unreadNotificationCount = app(\App\Services\NotificationService::class)->unreadCount($authUser);
        } catch (\Throwable) {
            $unreadNotificationCount = 0;
        }
    }
    $themePreference = $themeState['theme'] ?? null;
    $themePreferenceSavedAt = $themeState['saved_at'] ?? null;
    $role = $authUser?->role ?? $role ?? 'student';

    // The XP balance is part of the shell, not of whichever page remembered to
    // pass it: a student reading a lesson or a report should still see it. A
    // failed read degrades to the existing value (or no readout) instead of
    // breaking the page; the ledger remains the source of truth.
    if ($role === 'student' && $authUser !== null && ! isset($totalXp)) {
        try {
            $totalXp = app(\App\Services\DashboardService::class)->totalXp($authUser);
        } catch (\Throwable) {
            $totalXp = null;
        }
    }
    $homeRoute = \App\Support\RoleHome::routeName($role);
    $displayName = $authUser?->username ?? $profileName ?? 'OPERATOR';

    $studentItems = [
        ['heading' => 'Journey'],
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'gauge', 'primaryIcon' => false],
        ['route' => 'learning-path', 'label' => 'Learning Path', 'icon' => 'route', 'primaryIcon' => false],
        ['heading' => 'Learning record'],
        ['route' => 'progress', 'label' => 'Course Progress', 'icon' => 'chart-no-axes-column'],
        ['route' => 'timeline', 'label' => 'Timeline', 'icon' => 'history'],
        ['route' => 'xp-ledger', 'label' => 'XP Ledger', 'icon' => 'coins'],
        ['route' => 'reports.progress', 'label' => 'Progress Report', 'icon' => 'file-text'],
        ['route' => 'competency', 'label' => 'Competency', 'icon' => 'badge-check'],
        ['route' => 'performance', 'label' => 'Performance Summary', 'icon' => 'chart-line'],
        ['heading' => 'Support'],
        ['route' => 'recommendations', 'label' => 'Recommendations', 'icon' => 'sparkles'],
        ['route' => 'achievements', 'label' => 'Achievements', 'icon' => 'award'],
        ['route' => 'assessments', 'label' => 'Boss Challenges', 'icon' => 'target'],
    ];

    $instructorItems = [
        ['route' => 'students', 'label' => 'Students', 'icon' => 'users'],
        ['route' => 'classrooms', 'label' => 'My Classrooms', 'icon' => 'school'],
        ['heading' => 'System'],
        ['route' => 'activity', 'label' => 'Learning Activity', 'icon' => 'activity'],
        ['route' => 'course-analytics', 'label' => 'Course Analytics', 'icon' => 'chart-line'],
        ['route' => 'reports.teacher', 'label' => 'Reports', 'icon' => 'file-text'],
        ['route' => 'needs-attention', 'label' => 'Needs Attention', 'icon' => 'triangle-alert'],
    ];

    $adminItems = [
        ['route' => 'admin.dashboard', 'label' => 'Admin console', 'icon' => 'gauge'],
        ['route' => 'admin.users', 'label' => 'Users', 'icon' => 'users'],
        ['route' => 'admin.courses', 'label' => 'Courses', 'icon' => 'book-open'],
        ['route' => 'admin.classrooms', 'label' => 'Classrooms', 'icon' => 'school'],
        ['route' => 'admin.announcements', 'label' => 'Announcements', 'icon' => 'megaphone'],
        ['route' => 'admin.activity', 'label' => 'Audit trail', 'icon' => 'scroll-text'],
        ['route' => 'admin.analytics', 'label' => 'System analytics', 'icon' => 'chart-column'],
        ['route' => 'reports.admin-system', 'label' => 'System report', 'icon' => 'file-text'],
        ['route' => 'admin.system', 'label' => 'System status', 'icon' => 'server'],
    ];

    $items = match ($role) {
        'student' => $studentItems,
        'admin' => $adminItems,
        'teacher', 'instructor' => $instructorItems,
        default => [['route' => 'notifications', 'label' => 'Notifications']],
    };

    $activeRoute = request()->route()?->getName();
    $primaryRoutes = match ($role) {
        'student' => ['dashboard', 'learning-path'],
        'teacher', 'instructor' => ['students', 'classrooms', 'needs-attention', 'reports.teacher'],
        'admin' => ['admin.dashboard', 'admin.users', 'admin.classrooms', 'admin.courses'],
        default => [],
    };
    $topItems = array_values(array_filter($items, fn ($item) => in_array($item['route'] ?? null, $primaryRoutes, true)));
    // The popover is a record index, not a link dump: keep the authored
    // headings so eleven destinations stay scannable. The first group carries
    // the disclosure's own label, so its heading is not printed twice.
    $secondaryGroups = [];
    $secondaryGroup = null;
    foreach ($items as $item) {
        if (isset($item['heading'])) {
            $secondaryGroup = $item['heading'];

            continue;
        }
        if (! isset($item['route'])
            || in_array($item['route'], [...$primaryRoutes, 'notifications'], true)
            || ($authUser === null && $item['route'] === 'missions')) {
            continue;
        }
        $secondaryGroups[$secondaryGroup ?? 'Tools'][] = $item;
    }
    $secondaryItems = $secondaryGroups === [] ? [] : array_merge(...array_values($secondaryGroups));
    $secondaryLabel = match ($role) {
        'student' => 'Learning record',
        'teacher', 'instructor' => 'Teaching tools',
        'admin' => 'Operations',
        default => 'Tools',
    };
    $standalone = $standalone ?? false;
    // Public cover/login pages opt out of every shell (header, footer, sheet column).
    $guestShell = $guest ?? false;
    $studentPrototype = ! $guestShell && (($authUser !== null && $role === 'student') || ($studentPrototype ?? false));

    $teacherShell = in_array($role, ['teacher', 'instructor'], true) && ! $standalone && ! ($workspace ?? false) && ! $guestShell;
    $adminShell = $role === 'admin' && ! $standalone && ! ($workspace ?? false) && ! $studentPrototype && ! $guestShell;
    $isGuest = $guestShell || $authUser === null;
    $atlasShell = ! $isGuest;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CodeQuest') · CodeQuest</title>
    <script>
        // Apply the stored colour mode before first paint so dark never flashes.
        //
        // Two sources can hold a choice: this device (localStorage) and the
        // account (the server). Each carries a timestamp, and the newer one
        // wins — so a student who picks a theme and navigates straight away
        // keeps that choice even if the account write has not landed yet,
        // while a choice made on another device still converges here.
        (function () {
            var stored = @json($themePreference);
            var storedAt = @json($themePreferenceSavedAt);
            var device = null;
            try {
                var deviceTheme = localStorage.getItem('cq-theme');
                var deviceAtRaw = localStorage.getItem('cq-theme-at');
                if (deviceTheme === 'dark' || deviceTheme === 'light') {
                    device = {
                        theme: deviceTheme,
                        // A device value with no timestamp was written before
                        // the account learned about time. It is still the
                        // student's own choice, so it is honoured and stamped
                        // from here on rather than discarded.
                        saved_at: deviceAtRaw === null ? null : (parseInt(deviceAtRaw, 10) || 0)
                    };
                }
            } catch (e) {}
            // The terminal bay is the default room; a stored choice wins.
            var mode = 'dark';
            var fromDevice = false;
            if (device !== null) {
                var legacy = device.saved_at === null;
                var deviceIsNewer = legacy || storedAt === null || device.saved_at >= storedAt;
                if (deviceIsNewer) {
                    mode = device.theme;
                    fromDevice = true;
                    if (legacy) {
                        try { localStorage.setItem('cq-theme-at', String(Date.now())); } catch (e) {}
                    }
                }
            }
            if (! fromDevice && (stored === 'dark' || stored === 'light')) {
                mode = stored;
                try {
                    localStorage.setItem('cq-theme', mode);
                    localStorage.setItem('cq-theme-at', String(storedAt || 0));
                } catch (e) {}
            }
            mode = mode === 'dark' ? 'dark' : 'light';
            var dark = mode === 'dark';
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.dataset.themeMode = mode;
            document.documentElement.style.colorScheme = mode;
            if (fromDevice && (stored === 'dark' || stored === 'light') && stored !== mode) {
                // The account still holds another value: the client carries
                // this choice over, so it applies on every device.
                document.documentElement.dataset.themeSync = 'device';
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="atlas-ui {{ $isGuest ? 'guest-ui' : ($studentPrototype ? 'student-ui' : ($teacherShell ? 'teacher-ui' : ($adminShell ? 'admin-ui' : 'operator-ui'))) }} min-h-screen">
    <div class="flex min-h-screen flex-col">
        @unless ($standalone)
            @include('components.student-header')
        @endunless

        <div class="flex flex-1 min-w-0">
            <main id="main" tabindex="-1" class="min-w-0 flex-1 {{ ($workspace ?? false) ? 'w-full' : ($atlasShell ? 'w-full px-4 py-6 md:px-6 md:py-8 lg:px-8' : ($standalone ? 'w-full' : 'w-full p-4 sm:p-5 md:p-7')) }}">
                <div class="{{ ($workspace ?? false) ? 'w-full' : ($atlasShell ? 'sheet-column mx-auto w-full max-w-[1440px]' : ($standalone ? '' : 'mx-auto w-full max-w-[1440px]')) }}">
                    @yield('content')
                </div>
            </main>
        </div>

        @unless ($standalone || ($workspace ?? false) || $atlasShell)
        <footer class="manual-footer">
            <p>CODEQUEST // SYSTEM 404 LEARNING NETWORK</p>
            <p>STATUS: ONLINE · ACCESS: {{ strtoupper($role) }}</p>
        </footer>
        @endunless
    </div>

    @stack('scripts')
</body>
</html>
