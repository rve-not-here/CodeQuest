@extends('layouts.app', ['role' => $role, 'workspace' => true, 'studentPrototype' => true])

@section('title', $mission->title)

@section('content')
    @php
        $initialCode = old('code', $code);
        $initialCode = is_string($initialCode) ? $initialCode : ($initialCode === null ? '' : $code);
        $hasWorkspaceMessages = $errors->any() || session('mission_info') || session('mission_error')
            || session('hint_error') || session('reveal_error') || session('hint_revealed')
            || session('hint_flat') || session('reveal_flat') || session('reveal_requires_attempt') || session('solution_revealed') || session('draft_saved');

        $language = match ($course?->type) {
            'css' => 'CSS',
            'js', 'javascript' => 'JavaScript',
            default => 'HTML',
        };

        // Keep the final task paragraph separate so the brief can present it in order.
        $briefParagraphs = preg_split('/\r\n\r\n|\n\n/', trim((string) $mission->description)) ?: [];
        $task = null;
        if ($briefParagraphs !== []) {
            $last = trim((string) end($briefParagraphs));
            if (str_starts_with($last, 'Your task:')) {
                $task = ucfirst(trim(substr($last, strlen('Your task:'))));
                array_pop($briefParagraphs);
            }
        }

        // The lesson teaches with examples; the challenge is the task. Keeping
        // the same example here duplicated the target (and sometimes its exact
        // markup) in the workspace students are graded in.
        $briefParagraphs = array_values(array_filter(
            $briefParagraphs,
            fn (string $paragraph): bool => ! preg_match('/^\s*Example:/i', trim($paragraph)),
        ));

        // The graded workspace shows the target as a result, never as source:
        // HTML targets would otherwise print the exact answer markup.
        $target = (string) $mission->target_html;

        // The machine's own record of what happened, read as a list rather than
        // as floating cards: kind first, then what was done.
        $runRecords = [];
        if ($info = session('mission_info')) {
            $runRecords[] = ['Submit', $info['title'].' — '.$info['message'], 'ok'];
        }
        if ($failure = session('mission_error')) {
            $runRecords[] = ['Submit', $failure['title'].' — '.$failure['message'], 'bad'];
        }
        if ($hintError = session('hint_error')) {
            $runRecords[] = ['Hint', 'Insufficient XP — the hint costs '.$hintError['cost'].' XP and your balance is '.$hintError['balance'].'.', 'bad'];
        }
        if ($revealError = session('reveal_error')) {
            $runRecords[] = ['Solution', 'Insufficient XP — revealing costs '.$revealError['cost'].' XP and your balance is '.$revealError['balance'].'.', 'bad'];
        }
        if ($hintRevealed = session('hint_revealed')) {
            $runRecords[] = ['Hint', 'Hint '.$hintRevealed.' revealed — open View hints to read it.', 'warn'];
        }
        if (session('hint_flat')) {
            $runRecords[] = ['Hint', 'No hints remaining — all hints revealed, or none were available.', 'warn'];
        }
        if (session('reveal_flat')) {
            $runRecords[] = ['Solution', 'Not needed — no solution is available, or this challenge is already complete.', 'warn'];
        }
        if (session('reveal_requires_attempt')) {
            $runRecords[] = ['Solution', 'Attempt the challenge before revealing the reference solution.', 'warn'];
        }
        if (session('solution_revealed')) {
            $runRecords[] = ['Solution', 'The reference solution has been loaded.', 'warn'];
        }
        if (session('draft_saved')) {
            $runRecords[] = ['Draft', 'Saved to the pad — your code is ready when you return.', 'ok'];
        }
        if ($errors->any()) {
            $runRecords[] = ['Submit', 'Check your code — '.$errors->first(), 'bad'];
        }
    @endphp
    <article class="cq-challenge" data-workspace-fullscreen-root>
        <header>
                <span class="cq-case-index" aria-hidden="true">Entry {{ str_pad((string) $mission->order_num, 3, '0', STR_PAD_LEFT) }}</span>
                <a href="{{ route('mission.show', $mission) }}" class="cq-case-back" aria-label="Back to lesson"><x-icon name="arrow-left" size="16" /></a>
                <h1 id="challenge-title" class="cq-case-title">@foreach (explode('_', $mission->title) as $part)@unless ($loop->first)_<wbr>@endunless{{ $part }}@endforeach</h1>
                <span class="cq-case-rule" aria-hidden="true"></span>
                @if ($section?->title)
                    <p class="cq-case-concept">{{ $section->title }}</p>
                @endif
                <span class="cq-case-rule" aria-hidden="true"></span>
                <dl class="cq-case-registers">
                    <div><dt class="sr-only">Difficulty</dt><dd>{{ ucfirst(strtolower((string) $mission->difficulty)) }}</dd></div>
                    <div><dt class="sr-only">Reward</dt><dd class="is-numeric">+{{ $mission->points }} XP</dd></div>
                    <div><dt class="sr-only">Wrong attempt</dt><dd class="is-numeric">−{{ $wrongPenalty }} XP</dd></div>
                </dl>
                <span class="cq-case-state-group">
                    @if ($completed)
                        <span class="atlas-stamp atlas-stamp--grn"><x-icon name="check" size="12" />Completed</span>
                    @else
                        <p id="draft-status" class="cq-draft" data-state="{{ $hasDraft ? 'saved' : 'none' }}" aria-live="polite">Draft: <span>{{ $hasDraft ? 'Saved' : 'No saved draft' }}</span></p>
                        <span class="cq-draft-hint">Ctrl/⌘+S to save</span>
                    @endif
                </span>
        </header>
        <div class="challenge-workspace cq-workspace" data-workspace data-workspace-layout="tabs">
            <header class="cq-workspace-heading">
                <nav class="cq-workspace-tabs" role="tablist" aria-label="Workspace views" data-workspace-controls>
                    <button type="button" role="tab" id="workspace-tab-brief" data-pane-toggle="brief" aria-controls="workspace-brief" aria-selected="false" tabindex="-1" hidden>Instructions</button>
                    <button type="button" role="tab" id="workspace-tab-editor" data-pane-toggle="editor" aria-controls="workspace-editor" aria-selected="true" tabindex="0">Code</button>
                    <button type="button" role="tab" id="workspace-tab-preview" data-pane-toggle="preview" aria-controls="workspace-preview" aria-selected="false" tabindex="-1"><x-icon name="eye" size="16" />Preview</button>
                </nav>
                <div class="cq-workspace-actions" role="group" aria-label="Challenge actions">
                    <div class="cq-help-actions">
                        @if (! $completed && $totalHintCount > 0 && $revealedHintCount < $totalHintCount)
                            <form method="POST" action="{{ route('mission.hint', $mission) }}" data-challenge-action="hint" data-confirm-title="Reveal hint?" data-confirm-copy="This hint costs {{ $nextHintCost }} XP." data-confirm-label="Reveal hint">
                                @csrf
                                <input type="hidden" name="code" class="code-payload">
                                <button type="submit" class="btn btn-secondary"><x-icon name="lightbulb" size="16" />Hint · {{ $nextHintCost }} XP</button>
                            </form>
                        @endif
                        @if ($hints || (! $completed && $hasSolution))
                            <button type="button" class="btn btn-ghost" data-workspace-assistance-open>{{ $hints ? 'View hints' : 'Show solution · '.$revealCost.' XP' }}</button>
                        @endif
                    </div>
                    <div class="cq-evaluation-actions">
                        <button type="button" id="run" class="btn btn-secondary"><x-icon name="play" size="16" />Run</button>
                        @if ($completed)
                            <a href="{{ route('learning-path') }}" class="btn btn-primary">Learning path <x-icon name="arrow-right" data-arrow size="16" /></a>
                        @else
                            <form method="POST" action="{{ route('mission.submit', $mission) }}" class="workspace-submit" data-challenge-action="submit" data-confirm-title="Submit for evaluation?" data-confirm-copy="Submit sends your code for academic evaluation. A failed submission costs {{ $wrongPenalty }} XP. Run only updates the preview." data-confirm-label="Submit challenge">
                                @csrf
                                <input type="hidden" name="submission_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                                <input type="hidden" name="code" class="code-payload">
                                <button type="submit" class="btn btn-primary" aria-label="Submit challenge"><x-icon name="send" size="16" />Submit</button>
                            </form>
                        @endif
                    </div>
                </div>
                <button type="button" class="cq-icon-button" data-workspace-fullscreen aria-pressed="false" aria-label="Fullscreen">
                    <span data-workspace-fullscreen-icon="enter"><x-icon name="maximize" size="16" /></span>
                    <span data-workspace-fullscreen-icon="exit" hidden><x-icon name="minimize" size="16" /></span>
                    <span class="sr-only" data-workspace-fullscreen-label>Fullscreen</span>
                </button>
            </header>
            <section class="challenge-pane cq-instructions" id="workspace-brief" data-workspace-pane="brief" role="region" aria-labelledby="instructions-heading" tabindex="0">
                <header class="cq-instructions-heading">
                    <span id="instructions-heading">Instructions</span>
                    <a href="{{ route('mission.show', $mission) }}" class="cq-back-to-lesson" aria-label="Back to lesson"><x-icon name="arrow-left" size="16" />Back to lesson</a>
                    <button type="button" class="cq-icon-button" data-workspace-hide-brief aria-controls="workspace-brief" aria-expanded="true">
                        <span data-workspace-hide-icon="closed"><x-icon name="panel-left-close" size="16" /></span>
                        <span data-workspace-hide-icon="open" hidden><x-icon name="panel-left-open" size="16" /></span>
                        <span class="sr-only" data-workspace-hide-label>Hide instructions</span>
                    </button>
                </header>
                <div class="challenge-pane-body cq-lesson-reading">
                    <p class="cq-doc-label">The brief</p>
                    @if ($briefParagraphs !== [])
                        <div class="cq-lesson-context">
                            @foreach ($briefParagraphs as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    @else
                        <p>No lesson context has been added yet. Return to the lesson to review the available starter.</p>
                    @endif
                    @if ($task)
                        <p class="cq-doc-label">Your task</p>
                        <p class="cq-task-copy">{{ $task }}</p>
                    @endif
                    <details class="cq-guide" open>
                        <summary>How this workspace works</summary>
                        <ul>
                            <li><strong>Run</strong> updates the preview only. Nothing is submitted and no XP is spent.</li>
                            <li><strong>Submit</strong> evaluates the code. A failed attempt costs {{ $wrongPenalty }} XP.</li>
                            <li><strong>Hints</strong> start at {{ $firstHintCost }} XP and rise with each hint.</li>
                            <li><strong>Solution</strong> costs {{ $revealCost }} XP and unlocks after your first failed attempt.</li>
                        </ul>
                    </details>

                    @if ($target !== '')
                        <p class="cq-doc-label">Expected result</p>
                        <details class="atlas-lesson-reference cq-reference" open>
                            <summary>
                                <span>Target output</span>
                                <span>Result</span>
                            </summary>
                            <div class="atlas-lesson-reference-part">
                                <div class="atlas-lesson-reference-frame">
                                    <iframe id="reference-preview" title="Target output" sandbox="{{ in_array($course?->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}"></iframe>
                                </div>
                            </div>
                            <p class="cq-reference-note">Target only — write the source yourself.</p>
                        </details>
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
            </section>
            <section class="challenge-pane cq-preview-pane" id="workspace-preview" data-workspace-pane="preview" role="tabpanel" aria-labelledby="workspace-tab-preview" tabindex="0" hidden>
                <p class="cq-preview-note"><span>Preview only. No result is recorded.</span><span data-preview-state>{{ $hasDraft ? 'Restored draft' : 'Not run' }}</span></p>
                <div class="challenge-pane-body is-frame"><iframe id="preview-frame" sandbox="{{ in_array($course?->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}" title="Live preview"></iframe></div>
            </section>
        </div>
        <aside class="cq-runlog" data-challenge-notice role="status" aria-label="Challenge update" aria-live="polite" aria-atomic="true" @if (count($runRecords) === 0) hidden @endif>
            <div class="cq-runlog-messages" data-runlog-live>
                @foreach ($runRecords as [$kind, $text, $tone])
                    <p class="cq-runlog-line cq-runlog-line--{{ $tone }}"><b>{{ $kind }}</b><span>{{ $text }}</span></p>
                @endforeach
            </div>
            <button type="button" class="cq-runlog-dismiss" data-runlog-dismiss aria-label="Dismiss notification"><span aria-hidden="true">×</span></button>
        </aside>
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
                    <p class="challenge-assistance-cost">Balance {{ $totalXp }} XP · Hints from {{ $firstHintCost }} XP · Solution {{ $revealCost }} XP</p>
                    @if ($solutionRequirementMet)
                        <form method="POST" action="{{ route('mission.reveal', $mission) }}" data-challenge-action="reveal" data-confirm-title="Reveal reference solution?" data-confirm-copy="Revealing the reference solution costs {{ $revealCost }} XP and cannot be undone." data-confirm-label="Reveal solution">
                            @csrf<input type="hidden" name="code" class="code-payload"><button type="submit" class="btn btn-secondary">Reveal solution · {{ $revealCost }} XP</button>
                        </form>
                    @else
                        <p class="challenge-assistance-requirement">Submit an attempt first. The reference solution unlocks after your first failed attempt; hints are available now.</p>
                    @endif
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
                    <span class="atlas-stamp atlas-stamp--grn"><x-icon name="check" size="12" />Passed</span>
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
            var runLog = document.querySelector('[data-runlog-live]');
            var runNotice = document.querySelector('[data-challenge-notice]');
            var dismissNotice = document.querySelector('[data-runlog-dismiss]');
            var noticeTimer = null;

            function scheduleNoticeDismissal() {
                window.clearTimeout(noticeTimer);
                noticeTimer = window.setTimeout(function () {
                    if (runNotice) runNotice.hidden = true;
                }, 6500);
            }

            function pauseNoticeDismissal() {
                window.clearTimeout(noticeTimer);
            }

            function showNotice(line) {
                if (!runLog || !runNotice) return;
                runLog.replaceChildren(line);
                runNotice.hidden = false;
                scheduleNoticeDismissal();
            }

            function record(kind, text, tone) {
                if (!runLog || !runNotice) return;
                var line = document.createElement('p');
                line.className = 'cq-runlog-line' + (tone ? ' cq-runlog-line--' + tone : '');
                var label = document.createElement('b');
                label.textContent = kind;
                var copy = document.createElement('span');
                copy.textContent = text;
                line.appendChild(label);
                line.appendChild(copy);
                showNotice(line);
            }

            dismissNotice?.addEventListener('click', function () {
                pauseNoticeDismissal();
                if (runNotice) runNotice.hidden = true;
            });

            runNotice?.addEventListener('mouseenter', pauseNoticeDismissal);
            runNotice?.addEventListener('mouseleave', function () {
                if (!runNotice.hidden) scheduleNoticeDismissal();
            });
            runNotice?.addEventListener('focusin', pauseNoticeDismissal);
            runNotice?.addEventListener('focusout', function (event) {
                if (!runNotice.contains(event.relatedTarget) && !runNotice.hidden) scheduleNoticeDismissal();
            });

            if (runNotice && !runNotice.hidden) scheduleNoticeDismissal();

            var confirmationDialog = document.querySelector('[data-challenge-confirm]');
            var pendingAction = null;
            var confirmedForm = null;
            var assistanceDialog = document.querySelector('[data-challenge-assistance]');
            var assistanceReturn = null;
            var editorView = null;

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

            async function submitAssistanceInPlace(form) {
                var actionKind = form.dataset.challengeAction;
                var recordKind = actionKind === 'hint' ? 'Hint' : 'Solution';
                var submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) submitButton.disabled = true;

                try {
                    var response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    var responseHtml = await response.text();
                    if (!response.ok) throw new Error('Assistance request failed.');

                    var nextPage = new DOMParser().parseFromString(responseHtml, 'text/html');
                    var nextDialog = nextPage.querySelector('[data-challenge-assistance]');
                    if (!nextDialog || !assistanceDialog) throw new Error('Assistance response was incomplete.');

                    var latestRecord = Array.from(nextPage.querySelectorAll('.cq-runlog-line'))
                        .reverse()
                        .find(function (line) { return line.querySelector('b')?.textContent.trim() === recordKind; });
                    var actionMessage = latestRecord?.querySelector('span')?.textContent || 'Assistance request complete.';

                    if (actionKind === 'reveal' && /reference solution has been loaded/i.test(actionMessage)) {
                        var nextSource = nextPage.querySelector('#editor-source');
                        if (!nextSource) throw new Error('Solution response did not include the editor source.');
                        initial = nextSource.value;
                        source.value = initial;
                        if (editorView) {
                            editorView.dispatch({
                                changes: { from: 0, to: editorView.state.doc.length, insert: initial },
                            });
                        }
                    }

                    assistanceDialog.innerHTML = nextDialog.innerHTML;

                    var nextBalance = nextPage.querySelector('.atlas-xp');
                    var currentBalance = document.querySelector('.atlas-xp');
                    if (nextBalance && currentBalance) {
                        currentBalance.textContent = nextBalance.textContent;
                        currentBalance.setAttribute('aria-label', nextBalance.getAttribute('aria-label') || 'XP balance');
                    }

                    if (latestRecord && runLog) {
                        showNotice(document.importNode(latestRecord, true));
                    }
                    document.getElementById('workspace-status').textContent = actionMessage;

                    if (actionKind === 'hint' && /^Hint \d+ revealed\b/.test(actionMessage)) {
                        assistanceReturn = submitButton;
                        if (!assistanceDialog.open) assistanceDialog.showModal();
                    } else if (actionKind === 'reveal' && /reference solution has been loaded/i.test(actionMessage)) {
                        if (assistanceDialog.open) assistanceDialog.close();
                        editorView?.focus();
                    } else if (assistanceDialog.open) {
                        assistanceDialog.querySelector('[autofocus]')?.focus();
                    }
                } catch (error) {
                    record(recordKind, 'Could not complete this request. Please try again.', 'bad');
                    document.getElementById('workspace-status').textContent = 'Could not complete this request. Please try again.';
                } finally {
                    if (submitButton?.isConnected) submitButton.disabled = false;
                }
            }

            document.addEventListener('submit', function (event) {
                var form = event.target;
                if (!(form instanceof HTMLFormElement) || !['hint', 'reveal'].includes(form.dataset.challengeAction)) return;

                event.preventDefault();
                if (confirmedForm === form) {
                    confirmedForm = null;
                    submitAssistanceInPlace(form);
                    return;
                }

                confirmAction(form.dataset.confirmTitle, form.dataset.confirmCopy, function () {
                    var codePayload = form.querySelector('.code-payload');
                    if (codePayload && source) codePayload.value = source.value;
                    confirmedForm = form;
                    form.requestSubmit();
                }, form.dataset.confirmLabel, true);
            });

            var starter = {{ Illuminate\Support\Js::from($mission->broken_code ?? '') }};
            var initial = {{ Illuminate\Support\Js::from($initialCode) }};
            var target = {{ Illuminate\Support\Js::from($target) }};
            var host = document.getElementById('editor-host');
            var source = document.getElementById('editor-source');
            var runBtn = document.getElementById('run');
            var frame = document.getElementById('preview-frame');
            var reference = document.getElementById('reference-preview');
            var draftStatus = document.querySelector('#draft-status span');
            var draftForm = document.getElementById('draft-form');
            var completionOverlay = document.querySelector('[data-completion-overlay]');

            // Drafts save only on Ctrl/⌘+S. Warn before the one action that can
            // lose that work: leaving the page with unsaved edits.
            var draftDirty = false;
            window.addEventListener('beforeunload', function (event) {
                if (!draftDirty) return;
                event.preventDefault();
                event.returnValue = '';
            });

            // The reference output is the finished work, shown as it will look —
            // not the inline styles that produced it.
            if (reference) {
                reference.srcdoc = target;
            }

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
                                draftDirty = true;
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

                runBtn.addEventListener('click', function () {
                    confirmAction('Run preview?', 'Run displays your code in the preview. It does not grade, award XP or record a submission.', function () {
                        frame.srcdoc = cq.previewDocument(view.state.doc.toString(), type, starter);
                        document.querySelector('[data-preview-state]').textContent = 'Updated locally';
                        record('Preview', 'Updated locally \u2014 no result recorded, no XP spent.');
                        document.getElementById('workspace-status').textContent = 'Preview updated. Your code was not graded.';
                        document.dispatchEvent(new CustomEvent('cq:workspace-pane', { detail: 'preview' }));
                    }, 'Run preview', false);
                });

                // Initial preview reflects any restored draft.
                frame.srcdoc = cq.previewDocument(view.state.doc.toString(), type, starter);

                document.addEventListener('submit', function (event) {
                    var form = event.target;
                    if (!(form instanceof HTMLFormElement) || !form.matches('[data-challenge-action]')) return;
                    if (form.dataset.challengeAction === 'hint' || form.dataset.challengeAction === 'reveal') return;

                    if (confirmedForm === form) {
                        confirmedForm = null;
                        return;
                    }

                    event.preventDefault();
                    confirmAction(form.dataset.confirmTitle, form.dataset.confirmCopy, function () {
                        syncPayloads(view);
                        draftDirty = false;
                        confirmedForm = form;
                        form.requestSubmit();
                    }, form.dataset.confirmLabel, true);
                });

                document.addEventListener('cq:workspace-layout', function () { view.requestMeasure(); });
                draftForm?.addEventListener('submit', function () {
                    draftDirty = false;
                    setDraftState('saving', 'Saving...');
                });

                document.querySelectorAll('[data-workspace-assistance-open]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        assistanceReturn = button;
                        assistanceDialog.showModal();
                    });
                });
                assistanceDialog?.addEventListener('close', function () { assistanceReturn?.focus(); });
                if (assistanceDialog && {{ Illuminate\Support\Js::from((bool) session('hint_revealed')) }} && !completionOverlay) {
                    assistanceReturn = document.querySelector('[data-workspace-assistance-open]');
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
