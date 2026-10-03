@php
    $studentActiveRoute = request()->route()?->getName();
    $studentNavigation = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'active' => $studentActiveRoute === 'dashboard'],
        ['route' => 'learning-path', 'label' => 'Learn', 'active' => in_array($studentActiveRoute, ['learning-path', 'missions', 'mission.show', 'mission.experiment', 'knowledge-check.show', 'mission.challenge'], true)],
        ['route' => 'assessments', 'label' => 'Boss Challenges', 'active' => in_array($studentActiveRoute, ['assessments', 'assessment.show'], true)],
    ];
@endphp
<a href="#main" class="sr-only z-50 rounded-sm bg-accent px-3 py-2 text-sm font-semibold text-accent-ink focus:not-sr-only focus:fixed focus:top-2 focus:left-2">Skip to content</a>

<header class="sticky top-0 z-40 shrink-0 border-b border-line bg-canvas" data-role="student">
    <div class="mx-auto flex h-14 w-full max-w-[1440px] items-center gap-2 px-4 md:px-6 lg:px-8">
        <a href="{{ route($homeRoute) }}" class="mr-3 flex items-center gap-2.5 lg:mr-6" aria-label="CodeQuest home">
            <span class="grid size-7 place-items-center rounded-sm border border-accent-line bg-accent-soft font-mono text-[11px] font-semibold text-accent" aria-hidden="true">&gt;_</span>
            <span class="text-[15px] font-semibold tracking-tight text-fg">CodeQuest</span>
        </a>

        <nav aria-label="Primary" class="hidden md:block">
            <ul class="flex items-center">
                @foreach ($studentNavigation as $item)
                    <li><a href="{{ route($item['route']) }}" class="nav-link {{ $item['active'] ? 'text-accent' : '' }}" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div class="ml-auto flex items-center gap-3">
            @isset($totalXp)
                <a href="{{ route('xp-ledger') }}" class="hidden items-center gap-1.5 font-mono text-xs text-fg-muted hover:text-fg sm:flex" aria-label="XP balance {{ $totalXp }}. Open XP ledger">
                    <span class="size-1.5 rotate-45 bg-accent" aria-hidden="true"></span>
                    <span>XP <span class="text-fg">{{ number_format($totalXp) }}</span></span>
                </a>
            @endisset

            <details class="student-account relative cq-account-menu">
                <summary class="flex h-9 max-sm:h-11 cursor-pointer list-none items-center gap-2 rounded-sm border border-transparent pr-2 pl-1 text-fg-muted hover:border-line hover:bg-raised hover:text-fg focus-visible:border-accent [&::-webkit-details-marker]:hidden" aria-label="Open account menu">
                    <span class="grid size-7 place-items-center rounded-sm bg-overlay font-mono text-xs font-medium text-fg" aria-hidden="true">{{ strtoupper(substr($displayName, 0, 1)) }}</span>
                    <span class="hidden text-sm lg:inline">{{ $displayName }}</span>
                    <span class="font-mono text-xs" aria-hidden="true">⌄</span>
                </summary>

                <div class="panel absolute top-full right-0 z-50 mt-2 max-h-[calc(100dvh-5rem)] w-[min(28rem,calc(100vw-2rem))] overflow-y-auto bg-surface p-2 shadow-2xl shadow-black/50">
                    <div class="border-b border-line px-2.5 py-2">
                        <p class="truncate text-sm font-medium text-fg">{{ $displayName }}</p>
                        <p class="font-mono text-2xs text-fg-subtle">STUDENT</p>
                    </div>

                    <nav aria-label="Primary (mobile)" class="border-b border-line py-2 md:hidden">
                        <p class="eyebrow px-2.5 py-1">Navigate</p>
                        <ul class="grid grid-cols-2 gap-0.5">
                            @foreach ($studentNavigation as $item)
                                <li><a href="{{ route($item['route']) }}" class="menu-item {{ $item['active'] ? 'text-accent' : '' }}" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </nav>

                    <div class="grid grid-cols-1 gap-2 py-2 sm:grid-cols-2">
                        <nav aria-label="Learning record">
                            <p class="eyebrow px-2.5 py-1">Learning record</p>
                            <a href="{{ route('progress') }}" class="menu-item {{ $studentActiveRoute === 'progress' ? 'text-accent' : '' }}" @if ($studentActiveRoute === 'progress') aria-current="page" @endif>Course Progress</a>
                            <a href="{{ route('section-progress') }}" class="menu-item {{ $studentActiveRoute === 'section-progress' ? 'text-accent' : '' }}" @if ($studentActiveRoute === 'section-progress') aria-current="page" @endif>Section Progress</a>
                            <a href="{{ route('competency') }}" class="menu-item {{ $studentActiveRoute === 'competency' ? 'text-accent' : '' }}" @if ($studentActiveRoute === 'competency') aria-current="page" @endif>Competency</a>
                            <a href="{{ route('timeline') }}" class="menu-item {{ $studentActiveRoute === 'timeline' ? 'text-accent' : '' }}" @if ($studentActiveRoute === 'timeline') aria-current="page" @endif>Timeline</a>
                            <a href="{{ route('xp-ledger') }}" class="menu-item {{ $studentActiveRoute === 'xp-ledger' ? 'text-accent' : '' }}" @if ($studentActiveRoute === 'xp-ledger') aria-current="page" @endif>XP Ledger</a>
                            <a href="{{ route('reports.progress') }}" class="menu-item" @if ($studentActiveRoute === 'reports.progress') aria-current="page" @endif>Progress Report</a>
                        </nav>
                        <nav aria-label="More learning tools">
                            <p class="eyebrow px-2.5 py-1">More</p>
                            <a href="{{ route('recommendations') }}" class="menu-item {{ $studentActiveRoute === 'recommendations' ? 'text-accent' : '' }}" @if ($studentActiveRoute === 'recommendations') aria-current="page" @endif>Recommendations</a>
                            <a href="{{ route('achievements') }}" class="menu-item {{ $studentActiveRoute === 'achievements' ? 'text-accent' : '' }}" @if ($studentActiveRoute === 'achievements') aria-current="page" @endif>Achievements</a>
                            <a href="{{ route('notifications') }}" class="menu-item {{ $studentActiveRoute === 'notifications' ? 'text-accent' : '' }}" @if ($studentActiveRoute === 'notifications') aria-current="page" @endif>Notifications</a>
                        </nav>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="border-t border-line pt-2">
                        @csrf
                        <button type="submit" class="menu-item w-full text-left">Sign out</button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>
