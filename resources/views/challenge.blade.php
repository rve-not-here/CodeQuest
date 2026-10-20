@extends('layouts.app', ['role' => $role, 'workspace' => true, 'studentPrototype' => true])

@section('title', $mission->title)

@section('content')
    @php
        $initialCode = old('code', $code);
        $initialCode = is_string($initialCode) ? $initialCode : ($initialCode === null ? '' : $code);
        $hasWorkspaceMessages = $errors->any() || session('mission_info') || session('mission_error')
            || session('hint_error') || session('reveal_error') || session('hint_revealed')
            || session('hint_flat') || session('reveal_flat') || session('solution_revealed') || session('draft_saved');
    @endphp
    <article class="cq-challenge" data-workspace-fullscreen-root>
        <h1 class="sr-only">{{ $mission->title }}</h1>
        <div class="challenge-workspace cq-workspace" data-workspace data-workspace-layout="tabs">
            <header class="cq-workspace-heading">
                <nav class="cq-workspace-tabs" role="tablist" aria-label="Workspace views" data-workspace-controls>
                    <button type="button" role="tab" id="workspace-tab-brief" data-pane-toggle="brief" aria-controls="workspace-brief" aria-selected="false" tabindex="-1" hidden>Instructions</button>
                    <button type="button" role="tab" id="workspace-tab-editor" data-pane-toggle="editor" aria-controls="workspace-editor" aria-selected="true" tabindex="0">Code</button>
                    <button type="button" role="tab" id="workspace-tab-preview" data-pane-toggle="preview" aria-controls="workspace-preview" aria-selected="false" tabindex="-1"><x-workspace-icon name="eye" />Preview</button>
                </nav>
                <div class="cq-workspace-actions" role="group" aria-label="Challenge actions">
                    <div class="cq-help-actions">
                        @if (! $completed && $totalHintCount > 0 && $revealedHintCount < $totalHintCount)
                            <form method="POST" action="{{ route('mission.hint', $mission) }}" data-challenge-action="hint" data-confirm-title="Reveal hint?" data-confirm-copy="This hint costs {{ $nextHintCost }} XP." data-confirm-label="Reveal hint">
                                @csrf
                                <input type="hidden" name="code" class="code-payload">
                                <button type="submit" class="btn btn-secondary"><x-workspace-icon name="lightbulb" />Hint · {{ $nextHintCost }} XP</button>
                            </form>
                        @endif
                        @if ($hints)
                            <button type="button" class="btn btn-ghost" data-workspace-assistance-open>View hints</button>
                        @endif
                    </div>
                    <div class="cq-evaluation-actions">
                        <button type="button" id="run" class="btn btn-secondary"><x-workspace-icon name="play" />Run</button>
                        <form method="POST" action="{{ route('mission.submit', $mission) }}" class="workspace-submit" data-challenge-action="submit" data-confirm-title="Submit for evaluation?" data-confirm-copy="Submit sends your code for academic evaluation. A failed submission costs {{ $wrongPenalty }} XP. Run only updates the preview." data-confirm-label="Submit challenge">
                            @csrf
                            <input type="hidden" name="submission_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <input type="hidden" name="code" class="code-payload">
                            <button type="submit" class="btn btn-primary" aria-label="Submit challenge"><x-workspace-icon name="send" />Submit</button>
                        </form>
                    </div>
                </div>
                <details class="cq-workspace-options" data-shell-disclosure data-workspace-options>
                    <summary aria-label="Workspace options" title="Workspace options"><x-workspace-icon name="sliders-horizontal" /></summary>
                    <div class="cq-workspace-menu">
                        <p class="cq-workspace-context">{{ $mission->title }}@if ($course)<span>{{ $course->name }}@if ($section) · {{ $section->title }}@endif</span>@endif</p>
                        <p class="cq-workspace-metadata">{{ $mission->difficulty }} · +{{ $mission->points }} XP</p>
                        @if ($completed)<p class="cq-workspace-state"><span class="atlas-stamp atlas-stamp--grn">Completed</span></p>@endif
                        <p id="draft-status" class="challenge-draft-status" data-state="{{ session('draft_saved') || $hasDraft ? 'saved' : 'none' }}" aria-live="polite">Draft: <span>{{ session('draft_saved') || $hasDraft ? 'Saved' : 'No saved draft' }}</span></p>
                        @if (! $completed)<p class="cq-workspace-help">Save draft: <kbd>Ctrl/⌘ + S</kbd> within the workspace.</p>@endif
                        <button type="button" class="btn btn-ghost" data-workspace-hide-brief aria-controls="workspace-brief" aria-expanded="true"><span data-workspace-hide-icon="closed"><x-workspace-icon name="panel-left-close" /></span><span data-workspace-hide-icon="open" hidden><x-workspace-icon name="panel-left-open" /></span><span data-workspace-hide-label>Hide instructions</span></button>
                        <label class="cq-workspace-width">Instructions width<input type="range" min="240" max="480" step="1" value="340" data-workspace-width></label>
                        <button type="button" class="btn btn-ghost" data-workspace-reset>Reset panes</button>
                        @if ($hints || (! $completed && $hasSolution))<button type="button" class="btn btn-ghost" data-workspace-assistance-open>Assistance</button>@endif
                        <button type="button" class="btn btn-ghost" data-workspace-fullscreen aria-pressed="false"><span data-workspace-fullscreen-icon="enter"><x-workspace-icon name="maximize" /></span><span data-workspace-fullscreen-icon="exit" hidden><x-workspace-icon name="minimize" /></span><span data-workspace-fullscreen-label>Fullscreen</span></button>
                    </div>
                </details>
            </header>
            <section class="challenge-pane cq-instructions" id="workspace-brief" data-workspace-pane="brief" role="region" aria-labelledby="instructions-title" tabindex="0">
                <header class="cq-instructions-heading"><span>Instructions</span><a href="{{ route('mission.show', $mission) }}" class="cq-back-to-lesson" aria-label="Back to lesson"><x-workspace-icon name="arrow-left" />Back to lesson</a></header>
                <div class="challenge-pane-body cq-lesson-reading">
                    <div class="cq-brief-entry">
                        <span class="cq-brief-entry-label">Entry {{ str_pad((string) $mission->order_num, 3, '0', STR_PAD_LEFT) }} · Challenge</span>
                        @if ($completed)<span class="atlas-stamp atlas-stamp--grn">Completed</span>@endif
                    </div>
                    <h2 id="instructions-title">{{ $mission->title }}</h2>
                    <dl class="log-facts cq-brief-facts">
                        <div>
                            <dt>Difficulty</dt>
                            <dd>{{ $mission->difficulty }}</dd>
                        </div>
                        <div>
                            <dt>Reward</dt>
                            <dd class="is-numeric">+{{ $mission->points }} XP</dd>
                        </div>
                    </dl>
                    @if ($mission->description)<div class="cq-lesson-context whitespace-pre-line">{{ $mission->description }}</div>@else<p>No lesson context has been added yet. Return to the lesson to review the available starter.</p>@endif
                    @if ($mission->target_html)
                        <h3>Example</h3>
                        <pre class="log-listing" tabindex="0" aria-label="Reference output"><code>{{ $mission->target_html }}</code></pre>
                    @endif
                </div>
            </section>
            <div class="workspace-separator" role="separator" tabindex="0" aria-label="Resize instructions" aria-controls="workspace-brief" aria-orientation="vertical" aria-valuemin="240" aria-valuemax="480" aria-valuenow="340" data-workspace-resize="brief"></div>
            <section class="challenge-pane cq-code-pane" id="workspace-editor" data-workspace-pane="editor" role="tabpanel" aria-labelledby="workspace-tab-editor" tabindex="0">
                <h2 class="sr-only">Code editor</h2>
                <div class="challenge-pane-body editor-pane">
                    <div id="editor-host" class="challenge-pane-editor"></div>
                    <textarea name="terminal-code" id="editor-source" class="hidden" spellcheck="false">{{ $initialCode }}</textarea>
                </div>
                @if ($hasWorkspaceMessages)
                    <section class="cq-feedback" aria-labelledby="workspace-feedback-title">
                        <div class="flex items-center gap-3 flex-wrap"><h2 id="workspace-feedback-title">{{ session('mission_error') ? 'Submission feedback' : 'Workspace messages' }}</h2></div>
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
        <x-student-toast type="error" title="Insufficient XP">
            Hint costs {{ session('hint_error')['cost'] }} XP. Your balance is {{ session('hint_error')['balance'] }}.
        </x-student-toast>
    @endif
    @if (session('reveal_error') && session('mission_error') === null)
        <x-student-toast type="error" title="Insufficient XP">
            Solution reveal costs {{ session('reveal_error')['cost'] }} XP. Your balance is {{ session('reveal_error')['balance'] }}.
        </x-student-toast>
    @endif
    @if (session('hint_revealed'))
        <x-student-toast type="warning" title="Hint {{ session('hint_revealed') }} revealed">
            Open View hints to read it.
        </x-student-toast>
    @endif
    @if (session('hint_flat'))
        <x-student-toast type="info" title="No hints remaining">
            All hints revealed or no hints available.
        </x-student-toast>
    @endif
    @if (session('reveal_flat'))
        <x-student-toast type="info" title="Not needed">
            No solution is available, or this challenge is already complete.
        </x-student-toast>
    @endif
    @if (session('solution_revealed'))
        <x-student-toast type="warning" title="Solution revealed">
            The reference solution has been loaded.
        </x-student-toast>
    @endif
    @if (session('draft_saved'))
        <x-student-toast type="success" title="Draft saved">
            Your code is ready when you return.
        </x-student-toast>
    @endif
    @if ($errors->any())
        <x-student-toast type="error" title="Check your code">
            {{ $errors->first() }}
        </x-student-toast>
    @endif
    </div>

                    </section>
                @endif
            </section>
            <section class="challenge-pane cq-preview-pane" id="workspace-preview" data-workspace-pane="preview" role="tabpanel" aria-labelledby="workspace-tab-preview" tabindex="0" hidden>
                <p class="cq-preview-note"><span>Preview only. No result is recorded.</span><span data-preview-state>{{ $hasDraft ? 'Restored draft' : 'Not run' }}</span></p>
                <div class="challenge-pane-body is-frame"><iframe id="preview-frame" sandbox="{{ in_array($course?->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}" title="Live preview"></iframe></div>
            </section>
        </div>
        @if (! $completed)
            <form method="POST" action="{{ route('mission.draft', $mission) }}" id="draft-form" hidden>@csrf<input type="hidden" name="code" class="code-payload"></form>
        @endif
        <p class="sr-only" role="status" aria-atomic="true" id="workspace-status"></p>
        <dialog class="cq-confirm" data-challenge-confirm aria-labelledby="challenge-confirm-title" aria-describedby="challenge-confirm-copy">
            <h2 id="challenge-confirm-title"></h2><p id="challenge-confirm-copy"></p>
            <form method="dialog" class="cq-dialog-actions"><button type="submit" value="cancel" class="btn btn-secondary" autofocus>Cancel</button><button type="submit" value="confirm" class="btn btn-primary" data-confirm-action>Confirm</button></form>
        </dialog>
        @if ($hints || (! $completed && $hasSolution))
            <dialog class="cq-assistance" data-challenge-assistance aria-labelledby="assistance-title">
                <header><h2 id="assistance-title">Assistance</h2><form method="dialog"><button type="submit" class="btn btn-ghost" autofocus>Close</button></form></header>
                @if ($hints)
                    <ol class="challenge-hints">
                        @foreach ($hints as $index => $hintText)
                            <li class="challenge-hint">
                                <span class="challenge-hint-label">Hint {{ $index + 1 }}</span>
                                <p>{{ $hintText }}</p>
                            </li>
                        @endforeach
                    </ol>
                @else<p>No hints have been revealed.</p>@endif
                @if (! $completed && $hasSolution)
                    <form method="POST" action="{{ route('mission.reveal', $mission) }}" data-challenge-action="reveal" data-confirm-title="Reveal reference solution?" data-confirm-copy="Revealing the reference solution costs {{ $revealCost }} XP." data-confirm-label="Reveal solution">
                        @csrf<input type="hidden" name="code" class="code-payload"><button type="submit" class="btn btn-secondary">Show solution · {{ $revealCost }} XP</button>
                    </form>
                @endif
            </dialog>
        @endif
    @if (session('mission_success'))
        @php($completion = session('mission_success'))
        <div class="completion-overlay" data-completion-overlay>
            <section
                class="completion-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="completion-title"
                aria-describedby="completion-summary"
                data-completion-dialog
                tabindex="-1"
            >
                <div class="completion-heading">
                    <h2 id="completion-title">Challenge complete</h2>
                    <span class="atlas-stamp atlas-stamp--grn">Passed</span>
                </div>
                <p id="completion-summary" class="completion-summary">
                    {{ $mission->title }} passed server validation. Your progress is recorded.
                </p>

                <dl class="completion-results">
                    <div>
                        <dt>Validation</dt>
                        <dd class="text-success">Passed</dd>
                    </div>
                    <div>
                        <dt>XP earned</dt>
                        <dd class="text-success">+{{ $completion['xp_awarded'] }}</dd>
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
                    <button type="button" class="btn btn-ghost" data-completion-close>Review code</button>
                </div>
            </section>
        </div>
    @endif

    </article>

    @push('scripts')
    <script>
        (function () {
            document.querySelectorAll('[data-toast-dismiss]').forEach(function (button) {
                button.addEventListener('click', function () {
                    button.closest('.student-toast')?.remove();
                });
            });

            var starter = {{ Illuminate\Support\Js::from($mission->broken_code ?? '') }};
            var initial = {{ Illuminate\Support\Js::from($initialCode) }};
            var host = document.getElementById('editor-host');
            var source = document.getElementById('editor-source');
            var runBtn = document.getElementById('run');
            var frame = document.getElementById('preview-frame');
            var draftStatus = document.querySelector('#draft-status span');
            var draftForm = document.getElementById('draft-form');
            var completionOverlay = document.querySelector('[data-completion-overlay]');

            function setDraftState(state, text) {
                if (!draftStatus) return;
                draftStatus.textContent = text;
                document.getElementById('draft-status')?.setAttribute('data-state', state);
            }

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
                        CM.foundry,
                        CM.EditorView.updateListener.of(function (u) {
                            if (u.docChanged) {
                                syncPayloads(view);
                                if (draftStatus && draftStatus.textContent !== 'Unsaved changes') setDraftState('dirty', 'Unsaved changes');
                                var previewState = document.querySelector('[data-preview-state]');
                                if (previewState) previewState.textContent = 'Source changed. Run to update.';
                            }
                        }),
                    ],
                });

                syncPayloads(view);
                frame.sandbox.value = cq.previewSandbox(type, starter);

                var confirmationDialog = document.querySelector('[data-challenge-confirm]');
                var pendingAction = null;
                var confirmedForm = null;

                function confirmAction(title, copy, action, label, consequential) {
                    document.getElementById('challenge-confirm-title').textContent = title;
                    document.getElementById('challenge-confirm-copy').textContent = copy;
                    var confirmButton = confirmationDialog.querySelector('[data-confirm-action]');
                    confirmButton.textContent = label;
                    confirmButton.className = consequential ? 'btn btn-primary' : 'btn btn-secondary';
                    pendingAction = action;
                    confirmationDialog.returnValue = '';
                    confirmationDialog.showModal();
                }

                confirmationDialog.addEventListener('close', function () {
                    var action = pendingAction;
                    pendingAction = null;
                    if (confirmationDialog.returnValue === 'confirm' && action) action();
                });

                runBtn.addEventListener('click', function () {
                    confirmAction('Run preview?', 'Run displays your code in the preview. It does not grade, award XP or record a submission.', function () {
                        frame.srcdoc = cq.previewDocument(view.state.doc.toString(), type, starter);
                        document.querySelector('[data-preview-state]').textContent = 'Updated locally';
                        document.getElementById('workspace-status').textContent = 'Preview updated. Your code was not graded.';
                        document.dispatchEvent(new CustomEvent('cq:workspace-pane', { detail: 'preview' }));
                    }, 'Run preview', false);
                });

                // Initial preview reflects any restored draft.
                frame.srcdoc = cq.previewDocument(view.state.doc.toString(), type, starter);

                document.querySelectorAll('[data-challenge-action]').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (confirmedForm === form) {
                            confirmedForm = null;
                            return;
                        }
                        event.preventDefault();
                        confirmAction(form.dataset.confirmTitle, form.dataset.confirmCopy, function () {
                            syncPayloads(view);
                            confirmedForm = form;
                            form.requestSubmit();
                        }, form.dataset.confirmLabel, true);
                    });
                });

                document.addEventListener('cq:workspace-layout', function () { view.requestMeasure(); });
                draftForm?.addEventListener('submit', function () {
                    setDraftState('saving', 'Saving...');
                });

                var assistanceDialog = document.querySelector('[data-challenge-assistance]');
                var assistanceReturn = null;
                document.querySelectorAll('[data-workspace-assistance-open]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        assistanceReturn = button.closest('[data-workspace-options]')?.querySelector('summary') || button;
                        assistanceDialog.showModal();
                    });
                });
                assistanceDialog?.addEventListener('close', function () { assistanceReturn?.focus(); });
                if (assistanceDialog && {{ Illuminate\Support\Js::from((bool) session('hint_revealed')) }} && !completionOverlay) {
                    assistanceReturn = document.querySelector('[data-workspace-options] summary');
                    assistanceDialog.showModal();
                }

                document.querySelector('[data-workspace-fullscreen-root]').addEventListener('keydown', function (event) {
                    if (!draftForm || confirmationDialog.open || assistanceDialog?.open || event.defaultPrevented) return;
                    if ((event.ctrlKey || event.metaKey) && !event.altKey && !event.shiftKey && event.key.toLowerCase() === 's') {
                        event.preventDefault();
                        syncPayloads(view);
                        draftForm.requestSubmit();
                    }
                });

                if (completionOverlay) {
                    document.documentElement.classList.add('completion-open');
                    var dialog = completionOverlay.querySelector('[data-completion-dialog]');
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
