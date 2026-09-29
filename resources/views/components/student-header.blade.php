@php
    $studentActiveRoute = request()->route()?->getName();
@endphp
<a href="#main" class="sr-only z-50 rounded-sm bg-accent px-3 py-2 text-sm font-semibold text-accent-ink focus:not-sr-only focus:fixed focus:top-2 focus:left-2">Skip to content</a>

<header class="sticky top-0 z-40 shrink-0 border-b border-line bg-canvas" data-role="student">
    <div class="mx-auto flex h-14 w-full max-w-[1440px] items-center gap-2 px-4 md:px-6 lg:px-8">
        <a href="{{ route('dashboard') }}" class="mr-3 flex items-center gap-2.5 lg:mr-6" aria-label="CodeQuest dashboard">
            <span class="grid size-7 place-items-center rounded-sm border border-accent-line bg-accent-soft font-mono text-[11px] font-semibold text-accent" aria-hidden="true">&gt;_</span>
            <span class="text-[15px] font-semibold tracking-tight text-fg">CodeQuest</span>
        </a>

        <nav aria-label="Primary" class="hidden md:block">
            <ul class="flex items-center">
                <li><a href="{{ route('dashboard') }}" class="nav-link" @if ($studentActiveRoute === 'dashboard') aria-current="page" @endif>Dashboard</a></li>
                <li><a href="{{ route('learning-path') }}" class="nav-link {{ $studentActiveRoute === 'mission.show' ? 'text-accent' : '' }}" @if ($studentActiveRoute === 'learning-path') aria-current="page" @endif>Learn</a></li>
                <li><a href="{{ route('missions') }}" class="nav-link" @if ($studentActiveRoute === 'missions') aria-current="page" @endif>Missions</a></li>
                <li><a href="{{ route('assessments') }}" class="nav-link">Assessments</a></li>
            </ul>
        </nav>

        <div class="ml-auto flex items-center gap-3">
            <a href="{{ route('xp-ledger') }}" class="hidden items-center gap-1.5 font-mono text-xs text-fg-muted hover:text-fg sm:flex" aria-label="XP balance {{ $totalXp }}. Open XP ledger">
                <span class="size-1.5 rotate-45 bg-accent" aria-hidden="true"></span>
                <span>XP <span class="text-fg">{{ number_format($totalXp) }}</span></span>
            </a>

            <details class="student-account relative cq-account-menu">
                <summary class="flex h-9 max-sm:h-11 cursor-pointer list-none items-center gap-2 rounded-sm border border-transparent pr-2 pl-1 text-fg-muted hover:border-line hover:bg-raised hover:text-fg focus-visible:border-accent [&::-webkit-details-marker]:hidden" aria-label="Open account menu">
                    <span class="grid size-7 place-items-center rounded-sm bg-overlay font-mono text-xs font-medium text-fg" aria-hidden="true">{{ strtoupper(substr($displayName, 0, 1)) }}</span>
                    <span class="hidden text-sm lg:inline">{{ $displayName }}</span>
                    <span class="font-mono text-xs" aria-hidden="true">⌄</span>
                </summary>

                <div class="panel absolute top-full right-0 z-50 mt-2 w-[min(28rem,calc(100vw-2rem))] bg-surface p-2 shadow-2xl shadow-black/50">
                    <div class="border-b border-line px-2.5 py-2">
                        <p class="truncate text-sm font-medium text-fg">{{ $displayName }}</p>
                        <p class="font-mono text-2xs text-fg-subtle">STUDENT</p>
                    </div>

                    <nav aria-label="Primary (mobile)" class="border-b border-line py-2 md:hidden">
                        <p class="eyebrow px-2.5 py-1">Navigate</p>
                        <ul class="grid grid-cols-2 gap-0.5">
                            <li><a href="{{ route('dashboard') }}" class="menu-item {{ $studentActiveRoute === 'dashboard' ? 'text-accent' : '' }}" @if ($studentActiveRoute === 'dashboard') aria-current="page" @endif>Dashboard</a></li>
                            <li><a href="{{ route('learning-path') }}" class="menu-item {{ in_array($studentActiveRoute, ['learning-path', 'mission.show'], true) ? 'text-accent' : '' }}" @if ($studentActiveRoute === 'learning-path') aria-current="page" @endif>Learn</a></li>
                            <li><a href="{{ route('missions') }}" class="menu-item {{ $studentActiveRoute === 'missions' ? 'text-accent' : '' }}" @if ($studentActiveRoute === 'missions') aria-current="page" @endif>Missions</a></li>
                            <li><a href="{{ route('assessments') }}" class="menu-item">Assessments</a></li>
                        </ul>
                    </nav>

                    <div class="grid grid-cols-1 gap-2 py-2 sm:grid-cols-2">
                        <nav aria-label="Learning record">
                            <p class="eyebrow px-2.5 py-1">Learning record</p>
                            <a href="{{ route('progress') }}" class="menu-item">Course Progress</a>
                            <a href="{{ route('competency') }}" class="menu-item">Competency</a>
                            <a href="{{ route('timeline') }}" class="menu-item">Timeline</a>
                            <a href="{{ route('xp-ledger') }}" class="menu-item">XP Ledger</a>
                        </nav>
                        <nav aria-label="More learning tools">
                            <p class="eyebrow px-2.5 py-1">More</p>
                            <a href="{{ route('recommendations') }}" class="menu-item">Recommendations</a>
                            <a href="{{ route('achievements') }}" class="menu-item">Achievements</a>
                            <a href="{{ route('notifications') }}" class="menu-item">Notifications</a>
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
