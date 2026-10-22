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
                <p class="atlas-cover-lede">CodeQuest brings lessons, Knowledge Checks and Coding Challenges into one learning path, then keeps the evidence in one record.</p>

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
                    <p class="atlas-cover-register-figure">07 entries</p>
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
                    <li class="atlas-cover-entry">
                        <span class="atlas-cover-index" aria-hidden="true">05</span>
                        <div>
                            <h3 class="atlas-cover-entry-title">Learning path</h3>
                            <p class="atlas-cover-entry-copy">See the next lesson, review, practice or assessment-ready action.</p>
                        </div>
                    </li>
                    <li class="atlas-cover-entry">
                        <span class="atlas-cover-index" aria-hidden="true">06</span>
                        <div>
                            <h3 class="atlas-cover-entry-title">Learning timeline</h3>
                            <p class="atlas-cover-entry-copy">Read Knowledge Checks, coding, Boss results, XP and achievements in order.</p>
                        </div>
                    </li>
                    <li class="atlas-cover-entry">
                        <span class="atlas-cover-index" aria-hidden="true">07</span>
                        <div>
                            <h3 class="atlas-cover-entry-title">Performance summary</h3>
                            <p class="atlas-cover-entry-copy">Review Knowledge Check, Coding Challenge and Boss results together.</p>
                        </div>
                    </li>
                </ol>
            </section>

            <section class="atlas-cover-loop" aria-labelledby="loop-title">
                <div class="atlas-cover-loop-head">
                    <h2 id="loop-title">The learning loop</h2>
                    <p>Every step writes to the same record.</p>
                </div>
                <ol>
                    <li>
                        <span class="atlas-cover-loop-step">Study</span>
                        <p>Read the lesson and work through its examples.</p>
                    </li>
                    <li>
                        <span class="atlas-cover-loop-step">Check</span>
                        <p>Answer the Knowledge Check before the challenge opens.</p>
                    </li>
                    <li>
                        <span class="atlas-cover-loop-step">Practice</span>
                        <p>Complete the required Coding Challenges for the course.</p>
                    </li>
                    <li>
                        <span class="atlas-cover-loop-step">Prove</span>
                        <p>Clear the Boss Challenge and keep the result on record.</p>
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
