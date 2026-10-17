@php
    $authUser = auth()->user();
    $role = $authUser?->role ?? $role ?? 'student';
    $homeRoute = \App\Support\RoleHome::routeName($role);
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
        ['route' => 'reports.progress', 'label' => 'Progress Report'],
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
        ['route' => 'students', 'label' => 'Students'],
        ['route' => 'classrooms', 'label' => 'My Classrooms'],
        ['heading' => 'System'],
        ['route' => 'activity', 'label' => 'Learning Activity'],
        ['route' => 'course-analytics', 'label' => 'Course Analytics'],
        ['route' => 'reports.teacher', 'label' => 'Reports'],
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
        ['route' => 'reports.admin-system', 'label' => 'System Report'],
        ['route' => 'admin.system', 'label' => 'System Status'],
        ['route' => 'notifications', 'label' => 'Notifications'],
    ];

    $items = match ($role) {
        'student' => $studentItems,
        'admin' => $adminItems,
        'teacher', 'instructor' => $instructorItems,
        default => [['route' => 'notifications', 'label' => 'Notifications']],
    };

    $activeRoute = request()->route()?->getName();
    $primaryRoutes = match ($role) {
        'student' => ['dashboard', 'learning-path', 'assessments'],
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
    $studentPrototype = ($authUser !== null && $role === 'student') || ($studentPrototype ?? false);

    $adminNavGroups = [
        ['heading' => 'Overview', 'items' => [
            ['route' => 'admin.dashboard', 'label' => 'Admin Console'],
        ]],
        ['heading' => 'Manage', 'items' => [
            ['route' => 'admin.users', 'label' => 'Users'],
            ['route' => 'admin.courses', 'label' => 'Courses'],
            ['route' => 'admin.classrooms', 'label' => 'Classrooms'],
            ['route' => 'admin.announcements', 'label' => 'Announcements'],
        ]],
        ['heading' => 'Evidence', 'items' => [
            ['route' => 'admin.activity', 'label' => 'Audit Trail'],
            ['route' => 'admin.analytics', 'label' => 'System Analytics'],
            ['route' => 'reports.admin-system', 'label' => 'System Report'],
        ]],
        ['heading' => 'System', 'items' => [
            ['route' => 'admin.system', 'label' => 'System Status'],
        ]],
    ];
    $adminNavCurrent = fn (string $route): bool => $activeRoute === $route
        || ($activeRoute !== null && str_starts_with($activeRoute, $route.'.'));
    $showAdminSidebar = $role === 'admin' && ! $standalone && ! ($workspace ?? false) && ! $studentPrototype;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CodeQuest') · CodeQuest</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($studentPrototype)
        @vite('resources/css/student.css')
    @endif
</head>
<body class="atlas-ui {{ $studentPrototype ? 'student-ui' : 'console-ui console-ui--'.$role.' bg-surface text-ink font-sans' }} min-h-screen">
    <div class="flex min-h-screen flex-col">
        @unless ($standalone)
            @include('components.student-header')
        @endunless

        <div class="flex flex-1 min-w-0">
            @if ($showAdminSidebar)
                <aside class="console-sidebar" aria-label="Admin sections">
                    <div class="console-sidebar-inner">
                        <nav aria-label="Admin destinations">
                            @foreach ($adminNavGroups as $group)
                                <p class="console-sidebar-heading">{{ $group['heading'] }}</p>
                                @foreach ($group['items'] as $item)
                                    <a href="{{ route($item['route']) }}" @if ($adminNavCurrent($item['route'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                                @endforeach
                            @endforeach
                        </nav>
                    </div>
                </aside>
            @endif
            <main id="main" tabindex="-1" class="min-w-0 flex-1 {{ ($workspace ?? false) ? 'w-full' : ($studentPrototype ? 'w-full px-4 py-6 md:px-6 md:py-8 lg:px-8' : ($standalone ? 'w-full' : 'w-full p-4 sm:p-5 md:p-7')) }}">
                <div class="{{ ($workspace ?? false) ? 'w-full' : ($studentPrototype ? 'mx-auto w-full max-w-[1440px]' : ($standalone ? '' : 'mx-auto w-full max-w-[1440px]')) }}">
                    @yield('content')
                </div>
            </main>
        </div>

        @unless ($standalone || ($workspace ?? false) || $studentPrototype)
        <footer class="manual-footer">
            <p>CODEQUEST // SYSTEM 404 LEARNING NETWORK</p>
            <p>STATUS: ONLINE · ACCESS: {{ strtoupper($role) }}</p>
        </footer>
        @endunless
    </div>

    @stack('scripts')
</body>
</html>
