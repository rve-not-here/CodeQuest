@extends('layouts.app', ['standalone' => true, 'role' => 'student'])

@section('title', 'Welcome')

@section('content')
    <div class="guest-access guest-public">
        <a href="#welcome" class="atlas-skip">Skip to introduction</a>
        <header class="guest-access-header">
            <span class="atlas-brand">
                <span class="atlas-mark" aria-hidden="true">404</span>
                <span>CodeQuest</span>
            </span>
            <span class="guest-access-system">SYSTEM 404</span>
        </header>

        <div class="guest-access-layout">
            <section id="welcome" class="guest-public-intro" aria-labelledby="welcome-title" tabindex="-1">
                <h1 id="welcome-title">Programming learning<br>and assessment</h1>
                <p>CodeQuest brings lessons, Knowledge Checks and Coding Challenges into a structured learning path.</p>
                @auth
                    <a href="{{ route(\App\Support\RoleHome::routeName(auth()->user()->role)) }}" class="btn-primary">Open workspace</a>
                @else
                    <a href="{{ route('login') }}" class="btn-primary">Sign in</a>
                @endauth
            </section>

            <section class="guest-public-evidence" aria-labelledby="learning-title">
                <h2 id="learning-title">Learning in CodeQuest</h2>
                <dl>
                    <div>
                        <dt>Lessons</dt>
                        <dd>Read programming lessons and try examples.</dd>
                    </div>
                    <div>
                        <dt>Knowledge Checks</dt>
                        <dd>Review your understanding with formative feedback.</dd>
                    </div>
                    <div>
                        <dt>Coding Challenges</dt>
                        <dd>Write code, preview your work and submit it for evaluation.</dd>
                    </div>
                    <div>
                        <dt>Boss Challenges</dt>
                        <dd>Complete course-level assessments.</dd>
                    </div>
                </dl>
            </section>
        </div>
        <footer class="guest-public-footer">An integrated gamified learning and assessment system.</footer>
    </div>
@endsection
