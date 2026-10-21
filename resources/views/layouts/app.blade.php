@php
    $authUser = auth()->user();
    $themePreference = $authUser !== null
        ? app(\App\Services\ThemePreferenceService::class)->for($authUser)
        : null;
    $role = $authUser?->role ?? $role ?? 'student';
    $homeRoute = \App\Support\RoleHome::routeName($role);
    $displayName = $authUser?->username ?? $profileName ?? 'OPERATOR';

    $studentItems = [
        ['heading' => 'Journey'],
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'gauge', 'primaryIcon' => false],
        ['route' => 'learning-path', 'label' => 'Learning Path', 'icon' => 'route', 'primaryIcon' => false],
        ['heading' => 'Learning record'],
        ['route' => 'progress', 'label' => 'Course Progress', 'icon' => 'chart-no-axes-column'],
        ['route' => 'section-progress', 'label' => 'Section Progress', 'icon' => 'list-tree'],
        ['route' => 'timeline', 'label' => 'Timeline', 'icon' => 'history'],
        ['route' => 'xp-ledger', 'label' => 'XP Ledger', 'icon' => 'coins'],
        ['route' => 'reports.progress', 'label' => 'Progress Report', 'icon' => 'file-text'],
        ['route' => 'competency', 'label' => 'Competency', 'icon' => 'badge-check'],
        ['heading' => 'Support'],
        ['route' => 'recommendations', 'label' => 'Recommendations', 'icon' => 'sparkles'],
        ['route' => 'achievements', 'label' => 'Achievements', 'icon' => 'award'],
        ['route' => 'assessments', 'label' => 'Boss Challenges', 'icon' => 'target'],
        ['route' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell'],
        ['heading' => 'Reference'],
        ['route' => 'missions', 'label' => 'Challenge Index', 'icon' => 'library'],
    ];

    $instructorItems = [
        ['route' => 'students', 'label' => 'Students', 'icon' => 'users'],
        ['route' => 'classrooms', 'label' => 'My Classrooms', 'icon' => 'school'],
        ['heading' => 'System'],
        ['route' => 'activity', 'label' => 'Learning Activity', 'icon' => 'activity'],
        ['route' => 'course-analytics', 'label' => 'Course Analytics', 'icon' => 'chart-line'],
        ['route' => 'reports.teacher', 'label' => 'Reports', 'icon' => 'file-text'],
        ['route' => 'needs-attention', 'label' => 'Needs Attention', 'icon' => 'triangle-alert'],
        ['route' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell'],
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
        ['route' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell'],
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
    $secondaryItems = array_values(array_filter($items, fn ($item) => isset($item['route'])
        && ! in_array($item['route'], [...$primaryRoutes, 'notifications'], true)
        && ($authUser !== null || $item['route'] !== 'missions')));
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
        // An account-saved preference wins over this browser's localStorage and
        // is written back, so every device (and the asset-free error pages that
        // only read localStorage) converge on the account's choice.
        (function () {
            var stored = @json($themePreference);
            var mode = 'light';
            try {
                mode = localStorage.getItem('cq-theme') || 'light';
            } catch (e) {}
            if (stored === 'dark' || stored === 'light') {
                mode = stored;
                try {
                    localStorage.setItem('cq-theme', mode);
                } catch (e) {}
            }
            mode = mode === 'dark' ? 'dark' : 'light';
            var dark = mode === 'dark';
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.dataset.themeMode = mode;
            document.documentElement.style.colorScheme = mode;
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
