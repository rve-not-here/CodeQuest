@extends('layouts.app', ['standalone' => true, 'role' => 'student'])

@section('title', 'Sign in')

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
            <a href="{{ route('login') }}" class="cq-navbar-link ml-auto">Sign in</a>
        </div>
    </header>

    {{-- Hero split — freeCodeCamp grammar --}}
    <section class="max-w-[1360px] mx-auto grid items-center gap-10 px-4 md:px-6 py-14 lg:grid-cols-2 lg:gap-16 lg:py-20">
        <div class="max-w-xl">
            <h1 class="text-4xl md:text-5xl font-display font-black tracking-tight text-[#0a0a23] leading-[1.08]">
                Learn to code — for free.
            </h1>
            <p class="mt-5 text-lg md:text-xl text-[#2a2a40] leading-relaxed">
                Practice by building projects in the terminal, earn XP, and beat the Boss to certify your course.
            </p>
            <ul class="mt-8 space-y-3 text-[17px] text-[#3b3b4f]">
                <li class="flex items-start gap-3">
                    <svg class="mt-1 shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#198eee" stroke-width="3" stroke-linecap="square" aria-hidden="true">
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                    Mission-based lessons graded by the server, never decorative guesses.
                </li>
                <li class="flex items-start gap-3">
                    <svg class="mt-1 shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#198eee" stroke-width="3" stroke-linecap="square" aria-hidden="true">
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                    A live preview as you type, with SUBMIT running the real checks.
                </li>
                <li class="flex items-start gap-3">
                    <svg class="mt-1 shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#198eee" stroke-width="3" stroke-linecap="square" aria-hidden="true">
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                    XP, competency, and a course certificate earned one challenge at a time.
                </li>
            </ul>
            <a href="#signin" class="btn-primary mt-9 text-lg">Start learning</a>
        </div>

        <div id="signin" class="w-full max-w-md mx-auto lg:mx-0">
            <div class="panel p-6 md:p-8 scroll-mt-6">
                <h2 class="text-2xl font-display font-bold tracking-tight text-[#0a0a23]">Sign in</h2>
                <p class="mt-1 text-sm text-[#6f6f79]">Operators sign in to continue their mission.</p>

                @if ($errors->any())
                    <div class="mt-4">
                        <x-status-message type="error" title="Could not sign you in">
                            @foreach ($errors->all() as $error)
                                {{ $error }}<br>
                            @endforeach
                        </x-status-message>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="username" class="block text-sm font-bold text-[#2a2a40] mb-1.5">
                            Username
                        </label>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="terminal-input"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-bold text-[#2a2a40] mb-1.5">
                            Password
                        </label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="terminal-input"
                        >
                    </div>

                    <label class="flex items-center gap-2 text-sm text-[#2a2a40] cursor-pointer">
                        <input type="checkbox" name="remember" class="accent-[#198eee] w-4 h-4" value="1">
                        Remember me
                    </label>

                    <button type="submit" class="btn-primary w-full">Sign in</button>
                </form>

                <p class="mt-5 text-xs text-[#8f8f9a] leading-relaxed">
                    Authorized operators only. Sign-in activity is logged for the learning record.
                </p>
            </div>
        </div>
    </section>
@endsection