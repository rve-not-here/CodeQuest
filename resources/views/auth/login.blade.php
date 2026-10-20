@extends('layouts.app', ['standalone' => true, 'guest' => true, 'role' => 'student'])

@section('title', 'Sign in')

@section('content')
    <a href="#signin" class="atlas-skip">Skip to sign in</a>

    <div class="atlas-cover">
        <header class="atlas-cover-head">
            <a href="{{ route('login') }}" class="atlas-brand" aria-label="CodeQuest home">
                <span class="atlas-mark" aria-hidden="true">404</span>
                <span>CodeQuest</span>
            </a>
            <div class="atlas-cover-tools">
                <x-theme-toggle />
                <span class="atlas-stamp atlas-stamp--ink">System 404</span>
            </div>
        </header>

        <div class="atlas-cover-body">
            <section id="signin" class="atlas-cover-lead" aria-labelledby="access-title" tabindex="-1">
                <span class="atlas-lesson-entry-label">Access register · Credentials</span>
                <h1 id="access-title" class="atlas-cover-title atlas-cover-title--sm">Access CodeQuest</h1>
                <p class="atlas-cover-lede">Sign in with your CodeQuest account.</p>

                @if ($errors->any())
                    <div class="atlas-access-alert" role="alert">
                        <strong>Could not sign you in</strong>
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
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="atlas-access-form">
                    @csrf

                    <div class="atlas-access-field">
                        <label for="username" class="atlas-lesson-entry-label">Username</label>
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
                            <p id="username-error" class="atlas-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="atlas-access-field">
                        <label for="password" class="atlas-lesson-entry-label">Password</label>
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
                            <p id="password-error" class="atlas-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Sign in →</button>
                </form>

                <div class="atlas-access-notes">
                    <p>Sign-in activity is recorded.</p>
                    <p>No self-registration — accounts are issued by your instructor.</p>
                </div>
            </section>

            <aside class="atlas-cover-register" aria-labelledby="context-title">
                <div class="atlas-cover-register-head">
                    <h2 id="context-title" class="atlas-cover-register-title">What you can open</h2>
                    <p class="atlas-cover-register-figure">03 entries</p>
                </div>

                <ol>
                    <li class="atlas-cover-entry">
                        <span class="atlas-cover-index" aria-hidden="true">01</span>
                        <div>
                            <h3 class="atlas-cover-entry-title">Lessons &amp; Knowledge Checks</h3>
                            <p class="atlas-cover-entry-copy">Read the lesson, try its examples, then review your understanding.</p>
                        </div>
                    </li>
                    <li class="atlas-cover-entry">
                        <span class="atlas-cover-index" aria-hidden="true">02</span>
                        <div>
                            <h3 class="atlas-cover-entry-title">Coding Challenges</h3>
                            <p class="atlas-cover-entry-copy">Write code, preview your work and submit it for evaluation.</p>
                        </div>
                    </li>
                    <li class="atlas-cover-entry">
                        <span class="atlas-cover-index" aria-hidden="true">03</span>
                        <div>
                            <h3 class="atlas-cover-entry-title">Boss Challenges</h3>
                            <p class="atlas-cover-entry-copy">Complete the course-level assessment.</p>
                        </div>
                    </li>
                </ol>

                <p class="atlas-cover-note">Your account determines the workspace you can access.</p>
            </aside>
        </div>

        <footer class="atlas-cover-foot">
            <p>Records · XP · Competency</p>
            <p>CodeQuest · System 404</p>
        </footer>
    </div>
@endsection
