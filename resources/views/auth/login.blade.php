@extends('layouts.app', ['standalone' => true, 'role' => 'student'])

@section('title', 'Sign in')

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
            <a href="{{ route('login') }}" class="cq-navbar-link ml-auto">Sign in</a>
        </div>
    </header>

    <section class="mx-auto grid max-w-[1360px] grid-cols-1 items-center gap-10 px-4 py-14 md:px-6 lg:grid-cols-2 lg:gap-16 lg:py-20">
        <div class="max-w-xl">
            <p class="terminal-kicker text-phosphor">SYSTEM 404 // ACADEMIC ACCESS TERMINAL</p>
            <h1 class="mt-4 font-display text-4xl font-bold leading-[1.08] tracking-[-0.045em] text-ink md:text-5xl">
                Restore the system.<br>Learn by building.
            </h1>
            <p class="mt-5 text-lg leading-relaxed text-static md:text-xl">
                Read concise lessons, solve real coding challenges, and advance through a server-validated learning path.
            </p>
            <ul class="mt-8 space-y-4 text-[15px] text-ink">
                <li class="flex items-start gap-3">
                    <span class="text-cyan" aria-hidden="true">[01]</span>
                    Mission-based lessons graded by the server, never decorative guesses.
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-cyan" aria-hidden="true">[02]</span>
                    A live preview as you type, with SUBMIT running the real checks.
                </li>
                <li class="flex items-start gap-3">
                    <span class="text-cyan" aria-hidden="true">[03]</span>
                    XP, competency, and a course certificate earned one challenge at a time.
                </li>
            </ul>
            <a href="#signin" class="btn-primary mt-9 text-lg">Start learning</a>
        </div>

        <div id="signin" class="w-full max-w-md mx-auto lg:mx-0">
            <div class="panel p-6 md:p-8 scroll-mt-6">
                <p class="terminal-kicker text-phosphor">IDENTITY CHECK</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-ink">Sign in</h2>
                <p class="mt-1 text-sm text-static">Operators sign in to continue their mission.</p>

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
                        <label for="username" class="mb-1.5 block text-sm font-bold text-ink">
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
                        <label for="password" class="mb-1.5 block text-sm font-bold text-ink">
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

                    <button type="submit" class="btn-primary w-full">Sign in</button>
                </form>

                <p class="mt-5 text-xs leading-relaxed text-static">
                    Authorized operators only. Sign-in activity is logged for the learning record.
                </p>
            </div>
        </div>
    </section>
@endsection
