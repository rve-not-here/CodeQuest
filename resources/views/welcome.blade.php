@extends('layouts.app', ['standalone' => true, 'role' => 'student'])

@section('title', 'Welcome')

@section('content')
    <header class="cq-topnav">
        <div class="mx-auto flex h-[68px] max-w-[1360px] items-center gap-4 px-4 md:px-6">
            <a href="{{ route('login') }}" class="cq-brand" aria-label="CodeQuest home">
                <span class="cq-brand-mark" aria-hidden="true">404</span>
                <span>
                    <span class="cq-brand-name">CodeQuest</span>
                    <span class="cq-brand-subtitle">Learning terminal</span>
                </span>
            </a>
            <div class="ml-auto flex items-center gap-2">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="cq-navlink">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="cq-navlink">Sign in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="cq-navlink">Create account</a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <section class="mx-auto grid max-w-[1360px] grid-cols-1 items-center gap-10 px-4 py-14 md:px-6 lg:grid-cols-2 lg:gap-16 lg:py-20">
        <div class="max-w-xl">
            <p class="terminal-kicker text-phosphor">SYSTEM 404 // LEARNING NETWORK</p>
            <h1 class="mt-4 font-display text-4xl font-bold leading-[1.08] tracking-[-0.045em] text-ink md:text-5xl">
                Restore the system.<br>Learn by building.
            </h1>
            <p class="mt-5 text-lg leading-relaxed text-static md:text-xl">
                Follow a visible course path, learn each concept, then prove it in a focused coding workspace.
            </p>
            <div class="mt-9 flex flex-wrap items-center gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-primary text-lg">Go to Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary text-lg">Start learning</a>
                    @endauth
                @endif
            </div>
        </div>

        <div class="w-full max-w-lg mx-auto lg:mx-0" aria-hidden="true">
            <div class="panel overflow-hidden">
                <div class="cq-topnav px-4 py-2.5 flex items-center justify-between">
                    <span class="font-code text-xs font-semibold uppercase tracking-widest text-static">Learning path</span>
                    <span class="font-code text-xs text-phosphor">24% complete</span>
                </div>
                <div class="divide-y divide-phosphor/10">
                    @foreach ([
                        ['HTML Fundamentals', 'Basic Structure', 'done'],
                        ['HTML Fundamentals', 'Headings & Text', 'done'],
                        ['HTML Fundamentals', 'Lists and Links', 'next'],
                        ['HTML Fundamentals', 'Images & Media', 'locked'],
                        ['CSS Styling', 'Selectors', 'locked'],
                    ] as $i => $row)
                        <div class="flex items-center gap-3 px-4 py-3 text-sm">
                            @php
                                $state = $row[2];
                                $tone = $state === 'done' ? 'border-phosphor bg-phosphor text-void' : ($state === 'next' ? 'border-amber text-amber' : 'border-static/40 text-static');
                                $icon = $state === 'done' ? '✓' : ($state === 'next' ? '→' : '');
                            @endphp
                            <span class="flex h-5 w-5 items-center justify-center border text-[11px] font-bold {{ $tone }}">
                                {{ $icon }}
                            </span>
                            <span class="w-40 shrink-0 font-semibold text-ink">{{ $row[0] }}</span>
                            <span class="truncate text-static">{{ $row[1] }}</span>
                            @if ($state === 'next')
                                <span class="ml-auto text-xs font-bold uppercase tracking-wide text-amber">Current</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <footer class="manual-footer">
        <p>CODEQUEST // SYSTEM 404 LEARNING NETWORK</p>
        <p>STATUS: ONLINE</p>
    </footer>
@endsection
