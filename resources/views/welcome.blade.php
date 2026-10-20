@extends('layouts.app', ['standalone' => true, 'guest' => true, 'role' => 'student'])

@section('title', 'Welcome')

@section('content')
    <a href="#welcome" class="atlas-skip">Skip to introduction</a>

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
            <section id="welcome" class="atlas-cover-lead" aria-labelledby="welcome-title" tabindex="-1">
                <span class="atlas-lesson-entry-label">Field manual · Programming learning</span>
                <h1 id="welcome-title" class="atlas-cover-title">Programming learning<br>and assessment</h1>
                <p class="atlas-cover-lede">CodeQuest brings lessons, Knowledge Checks and Coding Challenges into a structured learning path.</p>

                <div class="atlas-cover-actions">
                    @auth
                        <a href="{{ route(\App\Support\RoleHome::routeName(auth()->user()->role)) }}" class="btn btn-primary">Open workspace →</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">Sign in →</a>
                    @endauth
                </div>

                <p class="atlas-cover-note">No self-registration — accounts are issued by your instructor. Sign-in activity is recorded.</p>
            </section>

            <section class="atlas-cover-register" aria-labelledby="learning-title">
                <div class="atlas-cover-register-head">
                    <h2 id="learning-title" class="atlas-cover-register-title">Contents</h2>
                    <p class="atlas-cover-register-figure">04 entries</p>
                </div>

                <ol>
                    <li class="atlas-cover-entry">
                        <span class="atlas-cover-index" aria-hidden="true">01</span>
                        <div>
                            <h3 class="atlas-cover-entry-title">Lessons</h3>
                            <p class="atlas-cover-entry-copy">Read programming lessons and try examples.</p>
                        </div>
                    </li>
                    <li class="atlas-cover-entry">
                        <span class="atlas-cover-index" aria-hidden="true">02</span>
                        <div>
                            <h3 class="atlas-cover-entry-title">Knowledge Checks</h3>
                            <p class="atlas-cover-entry-copy">Review your understanding with formative feedback.</p>
                        </div>
                    </li>
                    <li class="atlas-cover-entry">
                        <span class="atlas-cover-index" aria-hidden="true">03</span>
                        <div>
                            <h3 class="atlas-cover-entry-title">Coding Challenges</h3>
                            <p class="atlas-cover-entry-copy">Write code, preview your work and submit it for evaluation.</p>
                        </div>
                    </li>
                    <li class="atlas-cover-entry">
                        <span class="atlas-cover-index" aria-hidden="true">04</span>
                        <div>
                            <h3 class="atlas-cover-entry-title">Boss Challenges</h3>
                            <p class="atlas-cover-entry-copy">Complete course-level assessments.</p>
                        </div>
                    </li>
                </ol>
            </section>
        </div>

        <footer class="atlas-cover-foot">
            <p>An integrated gamified learning and assessment system.</p>
            <p>CodeQuest · System 404</p>
        </footer>
    </div>
@endsection
