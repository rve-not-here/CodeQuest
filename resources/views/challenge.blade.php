@extends('layouts.app', ['role' => $role, 'workspace' => true])

@section('title', $mission->title)

@section('content')
    <header class="challenge-context-bar">
        <nav aria-label="Challenge navigation">
            <a href="{{ route('mission.show', $mission) }}" class="terminal-link">← BACK TO LESSON</a>
        </nav>
        <div class="min-w-0 flex-1">
            <p class="terminal-kicker">
                {{ $course?->name }}
                @if ($section)
                    / {{ $section->title }}
                @endif
            </p>
            <h1>{{ $mission->title }}</h1>
        </div>
        <div class="challenge-context-meta">
            <x-badge tone="{{ $mission->difficulty === 'HARD' ? 'alert' : ($mission->difficulty === 'MEDIUM' ? 'amber' : 'cyan') }}">
                {{ $mission->difficulty }}
            </x-badge>
            <span>+{{ $mission->points }} XP on first pass</span>
        </div>
    </header>

    <div class="challenge-alert-stack">
    @if ($completed)
        <x-status-message type="success" title="MISSION COMPLETE" dismissible>
            You have already passed this mission.
        </x-status-message>
    @endif
    @if (session('mission_info'))
        <x-status-message type="info" title="{{ session('mission_info')['title'] }}">
            {{ session('mission_info')['message'] }}
        </x-status-message>
    @endif
    @if (session('mission_error'))
        <x-status-message type="error" title="{{ session('mission_error')['title'] }}">
            {{ session('mission_error')['message'] }}
        </x-status-message>
    @endif
    @if (session('hint_error') && session('mission_error') === null)
        <x-status-message type="error" title="INSUFFICIENT XP">
            Hint costs {{ session('hint_error')['cost'] }} XP. Your balance is {{ session('hint_error')['balance'] }}.
        </x-status-message>
    @endif
    @if (session('reveal_error') && session('mission_error') === null)
        <x-status-message type="error" title="INSUFFICIENT XP">
            Solution reveal costs {{ session('reveal_error')['cost'] }} XP. Your balance is {{ session('reveal_error')['balance'] }}.
        </x-status-message>
    @endif
    @if (session('hint_flat'))
        <x-status-message type="info" title="NO HINTS REMAINING">
            All hints revealed or no hints available.
        </x-status-message>
    @endif
    @if (session('reveal_flat'))
        <x-status-message type="info" title="NOT NEEDED">
            Solution reveal not needed on a completed mission.
        </x-status-message>
    @endif
    @if (session('solution_revealed'))
        <x-status-message type="warning" title="SOLUTION REVEALED">
            The reference solution has been loaded.
        </x-status-message>
    @endif
    </div>

    <div class="challenge-workspace">
        <section class="panel challenge-pane">
            <h2 class="challenge-pane-header">Challenge brief</h2>
            <div class="challenge-pane-body">
                <div class="space-y-4 text-[15px] leading-relaxed text-ink">
                    <div>
                        <p class="terminal-kicker text-phosphor">OBJECTIVE</p>
                        <p class="mt-2">{{ $mission->title }}</p>
                    </div>
                    @if ($mission->description)
                        <p>{{ $mission->description }}</p>
                    @else
                        <p class="text-phosphor-dim">No briefing attached to this mission.</p>
                    @endif
                    <div class="challenge-requirements">
                        <p><span aria-hidden="true">01</span> Write the solution in the editor.</p>
                        <p><span aria-hidden="true">02</span> Run a preview before submitting.</p>
                        <p><span aria-hidden="true">03</span> Submit for server validation.</p>
                    </div>
                    <p class="text-phosphor">PASS REWARD: +{{ $mission->points }} XP</p>
                    <p class="text-static">A failed authoritative submission costs {{ $wrongPenalty }} XP.</p>
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
                <textarea name="terminal-code" id="editor-source" class="hidden" spellcheck="false">{{ old('code', $code) }}</textarea>

                <div class="challenge-toolbar">
                    <button type="button" id="run" class="btn-ghost">RUN / PREVIEW</button>

                    <form method="POST" action="{{ route('mission.submit', $mission) }}" class="inline">
                        @csrf
                        <input type="hidden" name="code" class="code-payload">
                        <button type="submit" class="btn-primary">SUBMIT CHALLENGE</button>
                    </form>

                    @if (! $completed)
                    <form method="POST" action="{{ route('mission.draft', $mission) }}" class="inline" id="draft-form">
                        @csrf
                        <input type="hidden" name="code" class="code-payload">
                        <button type="submit" class="btn-ghost">SAVE DRAFT</button>
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
                                <button type="submit" class="btn-ghost">HINT {{ $revealedHintCount + 1 }}/{{ $totalHintCount }} · {{ $nextHintCost }} XP</button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('mission.reveal', $mission) }}" class="inline">
                                @csrf
                                <input type="hidden" name="code" class="code-payload">
                                <button type="submit" class="btn-ghost">SHOW SOLUTION · {{ $revealCost }} XP</button>
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
                    sandbox="allow-scripts"
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
                <p class="terminal-kicker text-phosphor">AUTHORITY RESPONSE // VALIDATED</p>
                <h2 id="completion-title">Challenge complete</h2>
                <p id="completion-summary" class="completion-summary">
                    {{ $mission->title }} passed server validation. Your progress is recorded.
                </p>

                <dl class="completion-results">
                    <div>
                        <dt>Validation</dt>
                        <dd class="text-phosphor">Passed</dd>
                    </div>
                    <div>
                        <dt>XP earned</dt>
                        <dd class="text-phosphor">+{{ $completion['xp_awarded'] }}</dd>
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
                    <a href="{{ route('learning-path') }}" class="btn-primary" data-completion-primary>CONTINUE TO LEARNING PATH →</a>
                    <button type="button" class="btn-ghost" data-completion-close>REVIEW CODE</button>
                </div>
            </section>
        </div>
    @endif

    @push('scripts')
    <script>
        (function () {
            var initial = JSON.parse('{!! json_encode(old('code', $code), JSON_HEX_APOS | JSON_HEX_QUOT) !!}');
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
