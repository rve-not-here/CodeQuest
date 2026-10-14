@extends('layouts.app', ['standalone' => true, 'role' => 'student'])

@section('title', 'Sign in')

@section('content')
    <div class="guest-access">
        <a href="#signin" class="atlas-skip">Skip to sign in</a>
        <header class="guest-access-header">
            <a href="{{ route('login') }}" class="atlas-brand" aria-label="CodeQuest home">
                <span class="atlas-mark" aria-hidden="true">404</span>
                <span>CodeQuest</span>
            </a>
            <span class="guest-access-system">SYSTEM 404</span>
        </header>

        <div class="guest-access-layout">
            <section id="signin" class="guest-access-form" aria-labelledby="access-title" tabindex="-1">
                <h1 id="access-title">Access CodeQuest</h1>
                <p class="guest-access-intro">Sign in with your CodeQuest account.</p>

                @if ($errors->any())
                    <x-status-message type="error" title="Could not sign you in" class="guest-access-errors">
                        <ul>
                            @foreach ($errors->getMessages() as $field => $messages)
                                @foreach ($messages as $message)
                                    <li>
                                        @if (in_array($field, ['username', 'password'], true))
                                            <a href="#{{ $field }}">{{ $message }}</a>
                                        @else
                                            {{ $message }}
                                        @endif
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </x-status-message>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="guest-access-field">
                        <label for="username">Username</label>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="terminal-input"
                            @if ($errors->has('username'))
                                aria-invalid="true"
                                aria-describedby="username-error"
                            @endif
                        >
                        @error('username')
                            <p id="username-error" class="guest-access-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="guest-access-field">
                        <label for="password">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="terminal-input"
                            @if ($errors->has('password'))
                                aria-invalid="true"
                                aria-describedby="password-error"
                            @endif
                        >
                        @error('password')
                            <p id="password-error" class="guest-access-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn-primary">Sign in</button>
                </form>
                <p class="guest-access-record">Sign-in activity is recorded.</p>
            </section>

            <aside class="guest-access-context" aria-labelledby="context-title">
                <h2 id="context-title">Programming learning<br>and assessment</h2>
                <p>Lessons, Knowledge Checks and Coding Challenges in one learning path.</p>
                <p>Your account determines the workspace you can access.</p>
            </aside>
        </div>
    </div>
@endsection
