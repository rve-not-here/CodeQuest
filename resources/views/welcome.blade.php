@extends('layouts.app', ['standalone' => true, 'role' => 'student'])

@section('title', 'Welcome')

@section('content')
    {{-- Navy brand bar --}}
    <header class="cq-topnav">
        <div class="max-w-[1360px] mx-auto flex h-16 items-center gap-4 px-4 md:px-6">
            <a href="{{ route('login') }}" class="flex items-center gap-2.5" aria-label="CodeQuest home">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="#198eee" aria-hidden="true">
                    <path d="M13 1 L4 14 h5 L9 23 L19 9 h-5 Z" />
                </svg>
                <span class="font-display text-xl font-bold tracking-tight text-white">CodeQuest</span>
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

    <section class="max-w-[1360px] mx-auto grid items-center gap-10 px-4 md:px-6 py-14 lg:grid-cols-2 lg:gap-16 lg:py-20">
        <div class="max-w-xl">
            <h1 class="text-4xl md:text-5xl font-display font-black tracking-tight text-[#0a0a23] leading-[1.08]">
                Learn to code — for free.
            </h1>
            <p class="mt-5 text-lg md:text-xl text-[#2a2a40] leading-relaxed">
                Practice by building projects in the terminal, earn XP, and beat the Boss to certify your course.
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
                    <span class="font-code text-xs font-semibold uppercase tracking-widest text-white/70">Learning path</span>
                    <span class="font-code text-xs text-[#69b6f5]">24% complete</span>
                </div>
                <div class="divide-y divide-[#e4e4e9]">
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
                                $tone = $state === 'done' ? 'bg-[#198eee]' : ($state === 'next' ? 'border-[#198eee]' : 'border-[#d0d0d5]');
                                $icon = $state === 'done' ? '✓' : ($state === 'next' ? '→' : '');
                            @endphp
                            <span class="flex items-center justify-center w-5 h-5 rounded-full border {{ $tone }} text-[11px] font-bold {{ $state === 'done' ? 'text-white' : ($state === 'next' ? 'text-[#198eee]' : 'text-[#d0d0d5]') }}">
                                {{ $icon }}
                            </span>
                            <span class="font-semibold text-[#0a0a23] w-40 shrink-0">{{ $row[0] }}</span>
                            <span class="text-[#3b3b4f] truncate">{{ $row[1] }}</span>
                            @if ($state === 'next')
                                <span class="ml-auto text-xs font-bold uppercase tracking-wide text-[#198eee]">Continue</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <footer class="manual-footer">
        <p>CodeQuest · Education for a more functional tomorrow.</p>
        <p>System 404 · Students, teachers, builders</p>
    </footer>
@endsection