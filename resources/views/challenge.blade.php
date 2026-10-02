@extends('layouts.app', ['role' => $role, 'workspace' => true, 'studentPrototype' => true])

@section('title', $mission->title)

@section('content')
    @php
        $initialCode = old('code', $code);
        $initialCode = is_string($initialCode) ? $initialCode : ($initialCode === null ? '' : $code);
    @endphp
    <header class="challenge-context-bar">
        <a href="{{ route('mission.show', $mission) }}" class="btn btn-ghost btn-sm shrink-0">← BACK TO LESSON</a>
        <div class="min-w-0 flex-1">
            <h1 class="truncate text-sm font-semibold">{{ $mission->title }}</h1>
            @if ($course)
                <p class="truncate text-xs text-fg-subtle">{{ $course->name }}@if ($section) · {{ $section->title }}@endif</p>
            @endif
        </div>
        <div class="challenge-context-meta">
            @if ($completed)
                <span class="badge badge-accent">MISSION COMPLETE</span>
            @endif
            <span class="badge badge-neutral">{{ $mission->difficulty }}</span>
            <span class="badge badge-accent">+{{ $mission->points }} XP</span>
        </div>
    </header>

    <div class="challenge-toast-stack" aria-label="Challenge messages">
    @if (session('mission_info'))
        <x-student-toast type="info" title="{{ session('mission_info')['title'] }}">
            {{ session('mission_info')['message'] }}
        </x-student-toast>
    @endif
    @if (session('mission_error'))
        <x-student-toast type="error" title="{{ session('mission_error')['title'] }}">
            {{ session('mission_error')['message'] }}
        </x-student-toast>
    @endif
    @if (session('hint_error') && session('mission_error') === null)
        <x-student-toast type="error" title="INSUFFICIENT XP">
            Hint costs {{ session('hint_error')['cost'] }} XP. Your balance is {{ session('hint_error')['balance'] }}.
        </x-student-toast>
    @endif
    @if (session('reveal_error') && session('mission_error') === null)
        <x-student-toast type="error" title="INSUFFICIENT XP">
            Solution reveal costs {{ session('reveal_error')['cost'] }} XP. Your balance is {{ session('reveal_error')['balance'] }}.
        </x-student-toast>
    @endif
    @if (session('hint_revealed'))
        <x-student-toast type="warning" title="HINT {{ session('hint_revealed') }} REVEALED">
            Read it in Assistance below.
        </x-student-toast>
    @endif
    @if (session('hint_flat'))
        <x-student-toast type="info" title="NO HINTS REMAINING">
            All hints revealed or no hints available.
        </x-student-toast>
    @endif
    @if (session('reveal_flat'))
        <x-student-toast type="info" title="NOT NEEDED">
            Solution reveal not needed on a completed mission.
        </x-student-toast>
    @endif
    @if (session('solution_revealed'))
        <x-student-toast type="warning" title="SOLUTION REVEALED">
            The reference solution has been loaded.
        </x-student-toast>
    @endif
    @if (session('draft_saved'))
        <x-student-toast type="success" title="DRAFT SAVED">
            Your code is ready when you return.
        </x-student-toast>
    @endif
    @if ($errors->any())
        <x-student-toast type="error" title="CHECK YOUR CODE">
            {{ $errors->first() }}
        </x-student-toast>
    @endif
    </div>

    <div class="challenge-workspace">
        <section class="panel challenge-pane">
            <h2 class="challenge-pane-header">Challenge brief</h2>
            <div class="challenge-pane-body">
                <div class="space-y-4 text-sm leading-6 text-fg-muted">
                    <div>
                        <p class="eyebrow text-accent">Objective</p>
                        <p class="mt-2 text-fg">{{ $mission->description ?: 'Read the lesson, then build your solution in the editor.' }}</p>
                    </div>
                    <div class="challenge-requirements">
                        <p><span aria-hidden="true">01</span> Write the solution in the editor.</p>
                        <p><span aria-hidden="true">02</span> Run a preview before submitting.</p>
                        <p><span aria-hidden="true">03</span> Submit for server validation.</p>
                    </div>
                    <p class="text-xs text-fg-subtle">A failed submission costs {{ $wrongPenalty }} XP.</p>
                </div>
            </div>
        </section>

        <section class="panel challenge-pane">
            <div class="challenge-pane-header">
                <h2>Code editor</h2>
                <p id="draft-status" class="challenge-draft-status" aria-live="polite">
                    Draft: <span>{{ session('draft_saved') || $hasDraft ? 'Saved' : 'No saved draft' }}</span>
                </p>
            </div>
            <div class="challenge-pane-body editor-pane">
                <div id="editor-host" class="challenge-pane-editor"></div>
                <textarea name="terminal-code" id="editor-source" class="hidden" spellcheck="false">{{ $initialCode }}</textarea>

                <div class="challenge-toolbar">
                    <button type="button" id="run" class="btn btn-secondary">RUN / PREVIEW</button>

                    <form method="POST" action="{{ route('mission.submit', $mission) }}" class="inline">
                        @csrf
                        <input type="hidden" name="code" class="code-payload">
                        <button type="submit" class="btn btn-primary">SUBMIT CHALLENGE</button>
                    </form>

                    @if (! $completed)
                    <form method="POST" action="{{ route('mission.draft', $mission) }}" class="inline" id="draft-form">
                        @csrf
                        <input type="hidden" name="code" class="code-payload">
                        <button type="submit" class="btn btn-ghost">SAVE DRAFT</button>
                    </form>
                    @endif
                </div>

                @if (! $completed || $hints)
                <section class="challenge-assistance" aria-labelledby="assistance-title">
                    <div class="challenge-assistance-heading">
                        <div>
                            <h3 id="assistance-title">Assistance</h3>
                            <p>Optional help uses XP and never changes validation rules.</p>
                        </div>

                        @if (! $completed && ($revealedHintCount < $totalHintCount || $totalHintCount === 0))
                        <div class="challenge-assistance-actions">
                            @if ($totalHintCount > 0 && $revealedHintCount < $totalHintCount)
                            <form method="POST" action="{{ route('mission.hint', $mission) }}" class="inline">
                                @csrf
                                <input type="hidden" name="code" class="code-payload">
                                <button type="submit" class="btn btn-ghost btn-sm">HINT {{ $revealedHintCount + 1 }}/{{ $totalHintCount }} · {{ $nextHintCost }} XP</button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('mission.reveal', $mission) }}" class="inline">
                                @csrf
                                <input type="hidden" name="code" class="code-payload">
                                <button type="submit" class="btn btn-ghost btn-sm">SHOW SOLUTION · {{ $revealCost }} XP</button>
                            </form>
                        </div>
                        @endif
                    </div>

                    @if ($hints)
                    <div class="challenge-hints">
                        @foreach ($hints as $index => $hintText)
                            <x-status-message type="warning" title="HINT {{ $index + 1 }}">
                                {{ $hintText }}
                            </x-status-message>
                        @endforeach
                    </div>
                    @endif
                </section>
                @endif
            </div>
        </section>

        <section class="panel challenge-pane">
            <h2 class="challenge-pane-header">Preview output</h2>
            <div class="challenge-pane-body is-frame">
                <iframe
                    id="preview-frame"
                    sandbox="{{ in_array($course?->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}"
                    title="Live preview"
                ></iframe>
            </div>
        </section>
    </div>

    @if (session('mission_success'))
        @php($completion = session('mission_success'))
        <div class="completion-overlay" data-completion-overlay>
            <section
                class="completion-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="completion-title"
                aria-describedby="completion-summary"
                tabindex="-1"
            >
                <p class="eyebrow text-accent">Validation passed</p>
                <h2 id="completion-title">Challenge complete</h2>
                <p id="completion-summary" class="completion-summary">
                    {{ $mission->title }} passed server validation. Your progress is recorded.
                </p>

                <dl class="completion-results">
                    <div>
                        <dt>Validation</dt>
                        <dd class="text-accent">Passed</dd>
                    </div>
                    <div>
                        <dt>XP earned</dt>
                        <dd class="text-accent">+{{ $completion['xp_awarded'] }}</dd>
                    </div>
                    <div>
                        <dt>Total XP</dt>
                        <dd>{{ $completion['xp_balance'] }}</dd>
                    </div>
                    @if ($completion['progress_before'] !== null && $completion['progress_after'] !== null)
                    <div>
                        <dt>Course progress</dt>
                        <dd>{{ $completion['progress_before'] }}% → {{ $completion['progress_after'] }}%</dd>
                    </div>
                    @endif
                </dl>

                @if ($completion['achievement'] !== null)
                    <p class="completion-achievement">
                        <span>Achievement unlocked</span>
                        <strong>{{ $completion['achievement'] }}</strong>
                    </p>
                @endif

                <div class="completion-actions">
                    <a href="{{ $completion['next_url'] }}" class="btn btn-primary" data-completion-primary>{{ $completion['next_label'] }} →</a>
                    <button type="button" class="btn btn-ghost" data-completion-close>REVIEW CODE</button>
                </div>
            </section>
        </div>
    @endif

    @push('scripts')
    <script>
        (function () {
            document.querySelectorAll('[data-toast-dismiss]').forEach(function (button) {
                button.addEventListener('click', function () {
                    button.closest('.student-toast')?.remove();
                });
            });

            var initial = {{ Illuminate\Support\Js::from($initialCode) }};
            var host = document.getElementById('editor-host');
            var source = document.getElementById('editor-source');
            var runBtn = document.getElementById('run');
            var frame = document.getElementById('preview-frame');
            var draftStatus = document.querySelector('#draft-status span');
            var draftForm = document.getElementById('draft-form');
            var completionOverlay = document.querySelector('[data-completion-overlay]');

            function syncPayloads(editor) {
                var value = editor.state.doc.toString();
                source.value = value;
                document.querySelectorAll('.code-payload').forEach(function (el) {
                    el.value = value;
                });
            }

            function init(cq) {
                var CM = cq.CodeMirror;

                var lang;
                var type = '{{ $course?->type ?? 'html' }}';
                if (type === 'js' || type === 'javascript') {
                    lang = CM.javascript();
                } else if (type === 'css') {
                    lang = CM.css();
                } else {
                    lang = CM.html();
                }

                var view = new CM.EditorView({
                    doc: initial,
                    parent: host,
                    extensions: [
                        CM.basicSetup,
                        lang,
                        CM.oneDark,
                        CM.EditorView.updateListener.of(function (u) {
                            if (u.docChanged) {
                                syncPayloads(view);
                                if (draftStatus) draftStatus.textContent = 'Unsaved changes';
                            }
                        }),
                    ],
                });

                syncPayloads(view);

                runBtn.addEventListener('click', function () {
                    frame.srcdoc = view.state.doc.toString();
                });

                // Initial preview reflects any restored draft.
                frame.srcdoc = view.state.doc.toString();

                draftForm?.addEventListener('submit', function () {
                    if (draftStatus) draftStatus.textContent = 'Saving...';
                });

                if (completionOverlay) {
                    document.documentElement.classList.add('completion-open');
                    var dialog = completionOverlay.querySelector('[role="dialog"]');
                    var closeButton = completionOverlay.querySelector('[data-completion-close]');
                    var focusable = Array.from(completionOverlay.querySelectorAll('a[href], button:not([disabled])'));

                    function closeCompletion() {
                        document.documentElement.classList.remove('completion-open');
                        completionOverlay.remove();
                        view.focus();
                    }

                    closeButton?.addEventListener('click', closeCompletion);
                    completionOverlay.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') {
                            event.preventDefault();
                            closeCompletion();
                            return;
                        }

                        if (event.key !== 'Tab' || focusable.length === 0) return;

                        var first = focusable[0];
                        var last = focusable[focusable.length - 1];

                        if (event.shiftKey && document.activeElement === first) {
                            event.preventDefault();
                            last.focus();
                        } else if (! event.shiftKey && document.activeElement === last) {
                            event.preventDefault();
                            first.focus();
                        }
                    });

                    window.requestAnimationFrame(function () {
                        (completionOverlay.querySelector('[data-completion-primary]') || dialog).focus();
                    });
                }
            }

            if (window.CodeQuest && window.CodeQuest.CodeMirror) {
                init(window.CodeQuest);
            } else {
                window.addEventListener('cq:codemirror-ready', function () {
                    init(window.CodeQuest);
                }, { once: true });
            }
        })();
    </script>
    @endpush
@endsection
