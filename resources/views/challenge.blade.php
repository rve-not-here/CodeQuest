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

    <div class="challenge-tabbar" role="tablist" aria-label="Challenge panes" data-challenge-tabs>
        <button type="button" class="challenge-tab" role="tab" id="cq-tab-editor" aria-controls="cq-panel-editor" aria-selected="true" data-mobile-tab="editor">Editor</button>
        <button type="button" class="challenge-tab" role="tab" id="cq-tab-brief" aria-controls="cq-panel-brief" aria-selected="false" data-mobile-tab="brief">Instructions</button>
        <button type="button" class="challenge-tab" role="tab" id="cq-tab-results" aria-controls="cq-panel-results" aria-selected="false" data-mobile-tab="results">Results</button>
    </div>

    <div class="challenge-pane-restore-bar" data-pane-restore-bar></div>

    <div class="challenge-workspace" data-challenge-workspace data-brief="open" data-results="open" data-mobile-tab="editor">
        <section class="challenge-pane challenge-pane--brief" id="cq-panel-brief" aria-labelledby="cq-brief-title">
            <div class="challenge-pane-header">
                <h2 id="cq-brief-title">Instructions</h2>
                <button
                    type="button"
                    class="challenge-pane-toggle"
                    data-pane-toggle="brief"
                    aria-controls="cq-panel-brief"
                    aria-label="Hide instructions"
                ><span aria-hidden="true">&laquo;</span></button>
            </div>
            <div class="challenge-pane-body">
                <div class="challenge-brief-block">
                    <p class="terminal-kicker text-phosphor">Objective</p>
                    <p>{{ $mission->title }}</p>
                </div>

                <div class="challenge-brief-block">
                    @if ($mission->description)
                        <p class="lesson-copy">{{ $mission->description }}</p>
                    @else
                        <p class="text-static">No briefing attached to this mission.</p>
                    @endif
                </div>

                <div class="challenge-brief-block">
                    <p class="terminal-kicker">Requirements</p>
                    <div class="challenge-requirements">
                        <p><span aria-hidden="true">01</span> Write the solution in the editor.</p>
                        <p><span aria-hidden="true">02</span> Run a preview before submitting.</p>
                        <p><span aria-hidden="true">03</span> Submit for server validation.</p>
                    </div>
                </div>

                <div class="challenge-brief-block">
                    <p class="text-phosphor">Pass reward: +{{ $mission->points }} XP</p>
                    <p class="text-static">A failed authoritative submission costs {{ $wrongPenalty }} XP.</p>
                </div>
            </div>
        </section>

        <div
            class="challenge-splitter challenge-splitter--brief"
            role="separator"
            tabindex="0"
            aria-orientation="vertical"
            aria-label="Resize instructions pane"
            aria-controls="cq-panel-brief"
            aria-valuemin="224" aria-valuemax="544" aria-valuenow="336"
            data-splitter="brief"
        ></div>

        <section class="challenge-pane challenge-pane--editor" id="cq-panel-editor" aria-labelledby="cq-editor-title">
            <div class="challenge-pane-header">
                <h2 id="cq-editor-title">Code editor</h2>
                <p id="draft-status" class="challenge-draft-status" aria-live="polite">
                    Draft: <span>{{ session('draft_saved') || $hasDraft ? 'Saved' : 'No saved draft' }}</span>
                </p>
            </div>
            <div class="challenge-pane-body editor-pane">
                <div id="editor-host" class="challenge-pane-editor"></div>
                <textarea name="terminal-code" id="editor-source" class="hidden" spellcheck="false">{{ old('code', $code) }}</textarea>

                @if (! $completed || $hints)
                <section class="challenge-assistance" aria-labelledby="assistance-title">
                    <div class="challenge-assistance-heading">
                        <div>
                            <h3 id="assistance-title">Assistance</h3>
                            <p>Optional help spends XP. It never changes the grading rules.</p>
                        </div>
                    </div>

                    @if ($hints)
                    <div class="challenge-hints">
                        @foreach ($hints as $index => $hintText)
                            <x-status-message type="warning" title="Hint {{ $index + 1 }}">
                                {{ $hintText }}
                            </x-status-message>
                        @endforeach
                    </div>
                    @endif
                </section>
                @endif
            </div>
        </section>

        <div
            class="challenge-splitter challenge-splitter--results"
            role="separator"
            tabindex="0"
            aria-orientation="vertical"
            aria-label="Resize results pane"
            aria-controls="cq-panel-results"
            aria-valuemin="256" aria-valuemax="640" aria-valuenow="384"
            data-splitter="results"
        ></div>

        <section class="challenge-pane challenge-pane--results" id="cq-panel-results" aria-labelledby="cq-results-title">
            <div class="challenge-pane-header">
                <h2 id="cq-results-title">Output</h2>
                <button
                    type="button"
                    class="challenge-pane-toggle"
                    data-pane-toggle="results"
                    aria-controls="cq-panel-results"
                    aria-label="Hide results"
                ><span aria-hidden="true">&raquo;</span></button>
            </div>

            <div class="challenge-output">
                <div class="challenge-output-tabs" role="tablist" aria-label="Output view">
                    <button type="button" class="challenge-output-tab" role="tab" id="cq-output-tab-preview" aria-controls="cq-output-preview" aria-selected="true">Preview</button>
                    <button type="button" class="challenge-output-tab" role="tab" id="cq-output-tab-console" aria-controls="cq-output-console" aria-selected="false">Console</button>
                    <button type="button" class="challenge-output-tab" role="tab" id="cq-output-tab-tests" aria-controls="cq-output-tests" aria-selected="false">Test results</button>
                </div>

                <div class="challenge-output-panel" id="cq-output-preview" role="tabpanel" aria-labelledby="cq-output-tab-preview">
                    <div class="challenge-pane-body is-frame">
                        <iframe
                            id="preview-frame"
                            sandbox="{{ in_array($course?->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}"
                            title="Live preview"
                        ></iframe>
                    </div>
                </div>

                <div class="challenge-output-panel challenge-test-feedback" id="cq-output-tests" role="tabpanel" aria-labelledby="cq-output-tab-tests" hidden>
                    @if (session('mission_error'))
                        <x-status-message type="error" title="{{ session('mission_error')['title'] }}">{{ session('mission_error')['message'] }}</x-status-message>
                    @elseif (session('mission_success'))
                        <x-status-message type="success" title="Submission passed">The server accepted this submission.</x-status-message>
                    @elseif ($completed)
                        <p>This mission is already complete.</p>
                    @else
                        <p>Submit your code to receive a server grading result. Run only refreshes the local preview.</p>
                    @endif
                </div>

                <div class="challenge-output-panel" id="cq-output-console" role="tabpanel" aria-labelledby="cq-output-tab-console" hidden>
                    <div class="challenge-console" id="challenge-console" role="log" aria-live="polite" aria-label="Run output">
                        <p class="challenge-console-empty" data-console-empty>
                            Run the challenge to see console output here.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="challenge-footer">
        <div class="challenge-footer-group">
            @if (! $completed && $totalHintCount > 0 && $revealedHintCount < $totalHintCount)
                <form method="POST" action="{{ route('mission.hint', $mission) }}">
                    @csrf
                    <input type="hidden" name="code" class="code-payload">
                    <button type="submit" class="btn-ghost" disabled data-busy-label="REVEALING HINT">
                        HINT {{ $revealedHintCount + 1 }}/{{ $totalHintCount }} &middot; {{ $nextHintCost }} XP
                    </button>
                </form>
            @endif

            @if (! $completed)
                <form method="POST" action="{{ route('mission.reveal', $mission) }}">
                    @csrf
                    <input type="hidden" name="code" class="code-payload">
                    <button type="submit" class="btn-ghost" disabled data-busy-label="LOADING SOLUTION">
                        SHOW SOLUTION &middot; {{ $revealCost }} XP
                    </button>
                </form>
            @else
                <p class="challenge-footer-note">Already passed. Editing stays open for practice.</p>
            @endif
        </div>

        <div class="challenge-footer-group">
            <p class="challenge-footer-note" data-footer-status aria-live="polite">Draft saves when you ask for it.</p>

            @if (! $completed)
                <form method="POST" action="{{ route('mission.draft', $mission) }}" id="draft-form">
                    @csrf
                    <input type="hidden" name="code" class="code-payload">
                    <button type="submit" class="btn-secondary" disabled data-busy-label="SAVING">SAVE DRAFT</button>
                </form>
            @endif

            <button type="button" id="run" class="btn-secondary" disabled>RUN</button>

            <form method="POST" action="{{ route('mission.submit', $mission) }}">
                @csrf
                <input type="hidden" name="code" class="code-payload">
                <button type="submit" class="btn-primary" disabled data-busy-label="VALIDATING">SUBMIT</button>
            </form>
        </div>
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
            var initial = document.getElementById('editor-source').value;
            var courseType = '{{ $course?->type ?? 'html' }}';
            var host = document.getElementById('editor-host');
            var source = document.getElementById('editor-source');
            var runBtn = document.getElementById('run');
            var frame = document.getElementById('preview-frame');
            var draftStatus = document.querySelector('#draft-status span');
            var draftForm = document.getElementById('draft-form');
            var completionOverlay = document.querySelector('[data-completion-overlay]');
            var workspace = document.querySelector('[data-challenge-workspace]');
            var footerStatus = document.querySelector('[data-footer-status]');

            function syncPayloads(editor) {
                var value = editor.state.doc.toString();
                source.value = value;
                document.querySelectorAll('.code-payload').forEach(function (el) {
                    el.value = value;
                });
            }

            /* ── Output pane: preview and test results share one column ── */
            var consoleHost = document.getElementById('challenge-console');
            var consoleEmpty = document.querySelector('[data-console-empty]');
            var lineCount = 0;

            function clearConsole() {
                lineCount = 0;
                if (!consoleHost) {
                    return;
                }
                consoleHost.textContent = '';
                if (consoleEmpty) {
                    consoleHost.appendChild(consoleEmpty);
                } else {
                    var blank = document.createElement('p');
                    blank.className = 'challenge-console-empty';
                    blank.textContent = 'Run the challenge to see console output here.';
                    consoleHost.appendChild(blank);
                }
            }

            function appendConsole(level, label, text) {
                if (!consoleHost || lineCount >= 200) {
                    return;
                }
                if (consoleEmpty) {
                    consoleEmpty.remove();
                }
                lineCount += 1;
                var row = document.createElement('div');
                row.className = 'challenge-console-line challenge-console-line--' + level;
                var index = document.createElement('span');
                index.textContent = String(lineCount).padStart(2, '0');
                var kind = document.createElement('strong');
                kind.textContent = label;
                var body = document.createElement('code');
                body.textContent = text;
                row.append(index, kind, body);
                consoleHost.appendChild(row);
                consoleHost.scrollTop = consoleHost.scrollHeight;
            }

            function selectOutputTab(name) {
                document.querySelectorAll('.challenge-output-tab').forEach(function (tab) {
                    var isTarget = tab.id === 'cq-output-tab-' + name;
                    tab.setAttribute('aria-selected', isTarget ? 'true' : 'false');
                    tab.tabIndex = isTarget ? 0 : -1;
                    var panel = document.getElementById(tab.getAttribute('aria-controls'));
                    if (panel) {
                        panel.hidden = !isTarget;
                    }
                });
            }

            document.querySelectorAll('.challenge-output-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    selectOutputTab(tab.id.replace('cq-output-tab-', ''));
                });
            });

            var outputTabs = Array.from(document.querySelectorAll('.challenge-output-tab'));
            document.querySelector('.challenge-output-tabs').addEventListener('keydown', function (event) {
                var index = outputTabs.indexOf(document.activeElement);
                if (index < 0) return;
                var next;
                if (event.key === 'ArrowRight') next = (index + 1) % outputTabs.length;
                else if (event.key === 'ArrowLeft') next = (index + outputTabs.length - 1) % outputTabs.length;
                else if (event.key === 'Home') next = 0;
                else if (event.key === 'End') next = outputTabs.length - 1;
                else return;
                event.preventDefault();
                selectOutputTab(outputTabs[next].id.replace('cq-output-tab-', ''));
                outputTabs[next].focus();
            });
            selectOutputTab(@json(session('mission_error') || session('mission_success') ? 'tests' : 'preview'));

            var tablist = document.querySelector('[data-challenge-tabs]');

            if (tablist) {
                var tabs = Array.prototype.slice.call(tablist.querySelectorAll('[role="tab"]'));

                function selectMobileTab(name, moveFocus) {
                    workspace.dataset.mobileTab = name;
                    tabs.forEach(function (tab) {
                        var isTarget = tab.dataset.mobileTab === name;
                        tab.setAttribute('aria-selected', isTarget ? 'true' : 'false');
                        tab.tabIndex = isTarget ? 0 : -1;
                        if (isTarget && moveFocus) {
                            tab.focus();
                        }
                    });
                }

                tabs.forEach(function (tab) {
                    tab.addEventListener('click', function () {
                        selectMobileTab(tab.dataset.mobileTab, false);
                    });
                });

                tablist.addEventListener('keydown', function (event) {
                    var current = tabs.indexOf(document.activeElement);
                    if (current < 0) {
                        return;
                    }
                    var next = null;
                    if (event.key === 'ArrowRight') {
                        next = (current + 1) % tabs.length;
                    } else if (event.key === 'ArrowLeft') {
                        next = (current - 1 + tabs.length) % tabs.length;
                    } else if (event.key === 'Home') {
                        next = 0;
                    } else if (event.key === 'End') {
                        next = tabs.length - 1;
                    } else {
                        return;
                    }
                    event.preventDefault();
                    selectMobileTab(tabs[next].dataset.mobileTab, true);
                });
            }

            /* ── Pane collapse: instructions and results fold, editor does not ── */
            var restoreButtons = [];

            function setPane(workspaceEl, name, open) {
                workspaceEl.dataset[name] = open ? 'open' : 'collapsed';
                workspaceEl.querySelector('[data-pane-toggle="' + name + '"]').setAttribute('aria-expanded', String(open));

                if (open) {
                    restoreButtons = restoreButtons.filter(function (entry) {
                        if (entry.pane === name) {
                            entry.button.remove();
                            workspaceEl.querySelector('[data-pane-toggle="' + name + '"]').focus();
                            return false;
                        }
                        return true;
                    });
                } else {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'challenge-pane-toggle';
                    button.dataset.paneToggle = name;
                    button.setAttribute('aria-controls', 'cq-panel-' + name);
                    button.setAttribute('aria-label', 'Show ' + name);
                    button.textContent = name === 'brief' ? 'Show instructions' : 'Show results';
                    button.setAttribute('aria-expanded', 'false');
                    document.querySelector('[data-pane-restore-bar]').appendChild(button);
                    button.focus();
                    restoreButtons.push({ pane: name, button: button });
                }
            }

            document.addEventListener('click', function (event) {
                var toggle = event.target.closest('[data-pane-toggle]');
                if (!toggle || !workspace) {
                    return;
                }
                var name = toggle.dataset.paneToggle;
                event.preventDefault();
                setPane(workspace, name, workspace.dataset[name] !== 'open');
            });

            /* ── Splitters: pointer drag plus arrow keys, editor keeps the slack ── */
            var LIMITS = { brief: [14, 34], results: [16, 40] };
            var desktop = window.matchMedia('(min-width: 1101px)');
            function remSize() { return parseFloat(getComputedStyle(document.documentElement).fontSize); }
            function currentRem(name) { return parseFloat(getComputedStyle(workspace).getPropertyValue('--' + name + '-width')); }
            function maxPaneWidth(name) {
                var other = name === 'brief' ? 'results' : 'brief';
                var otherWidth = workspace.dataset[other] === 'collapsed' ? 0 : currentRem(other);
                var handles = (workspace.dataset.brief === 'collapsed' ? 0 : 0.5) + (workspace.dataset.results === 'collapsed' ? 0 : 0.5);
                return Math.max(LIMITS[name][0], Math.min(LIMITS[name][1], workspace.clientWidth / remSize() - otherWidth - handles - 22));
            }
            function applyPaneWidth(name, value) {
                var settled = Math.max(LIMITS[name][0], Math.min(maxPaneWidth(name), value));
                workspace.style.setProperty('--' + name + '-width', settled + 'rem');
                var splitter = document.querySelector('[data-splitter="' + name + '"]');
                splitter.setAttribute('aria-valuemin', Math.round(LIMITS[name][0] * remSize()));
                splitter.setAttribute('aria-valuemax', Math.round(maxPaneWidth(name) * remSize()));
                splitter.setAttribute('aria-valuenow', Math.round(settled * remSize()));
                splitter.setAttribute('aria-valuetext', Math.round(settled * remSize()) + ' pixels');
            }
            function fitPanes() {
                if (!desktop.matches) return;
                applyPaneWidth('brief', currentRem('brief'));
                applyPaneWidth('results', currentRem('results'));
            }
            new ResizeObserver(fitPanes).observe(workspace);
            document.querySelectorAll('[data-splitter]').forEach(function (splitter) {
                var name = splitter.dataset.splitter;
                splitter.addEventListener('pointerdown', function (event) {
                    if (event.button !== 0) return;
                    splitter.setPointerCapture(event.pointerId);
                    splitter.dataset.dragging = 'true';
                    workspace.dataset.resizing = name;
                    event.preventDefault();
                });
                splitter.addEventListener('pointermove', function (event) {
                    if (splitter.dataset.dragging !== 'true') return;
                    var rect = workspace.getBoundingClientRect();
                    applyPaneWidth(name, (name === 'brief' ? event.clientX - rect.left : rect.right - event.clientX) / remSize());
                });
                function stopDrag(event) {
                    delete splitter.dataset.dragging;
                    delete workspace.dataset.resizing;
                    if (splitter.hasPointerCapture(event.pointerId)) splitter.releasePointerCapture(event.pointerId);
                }
                ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(function (type) { splitter.addEventListener(type, stopDrag); });
                splitter.addEventListener('keydown', function (event) {
                    var value = currentRem(name), step = event.shiftKey ? 4 : 1;
                    var direction = name === 'brief' ? 1 : -1;
                    if (event.key === 'ArrowLeft') value -= step * direction;
                    else if (event.key === 'ArrowRight') value += step * direction;
                    else if (event.key === 'Home') value = LIMITS[name][0];
                    else if (event.key === 'End') value = maxPaneWidth(name);
                    else return;
                    event.preventDefault();
                    applyPaneWidth(name, value);
                });
                splitter.addEventListener('dblclick', function () { applyPaneWidth(name, name === 'brief' ? 21 : 24); });
            });

            window.addEventListener('pointerup', function () {
                document.querySelectorAll('[data-splitter]').forEach(function (splitter) { delete splitter.dataset.dragging; });
                delete workspace.dataset.resizing;
            });
            document.querySelectorAll('[data-pane-toggle]').forEach(function (button) { button.setAttribute('aria-expanded', 'true'); });
            function syncPaneRoles() {
                document.querySelectorAll('.challenge-pane').forEach(function (pane) {
                    if (desktop.matches) {
                        pane.removeAttribute('role');
                        pane.setAttribute('aria-labelledby', pane.querySelector('h2').id);
                    } else {
                        pane.setAttribute('role', 'tabpanel');
                        pane.setAttribute('aria-labelledby', pane.id.replace('panel', 'tab'));
                    }
                });
            }
            desktop.addEventListener('change', syncPaneRoles);
            syncPaneRoles();

            /* ── Busy feedback. Without it a slow submit looks like a dead click. ── */
            document.querySelectorAll('form[method="POST"]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    var button = form.querySelector('[data-busy-label]');
                    if (!button) {
                        return;
                    }
                    if (form.closest('.challenge-footer') && !window.CodeQuest?.CodeMirror) return;
                    var original = button.textContent;
                    button.dataset.idleLabel = original;
                    button.textContent = button.dataset.busyLabel;
                    button.setAttribute('aria-busy', 'true');
                    button.disabled = true;
                });
            });

            function init(cq) {
                var CM = cq.CodeMirror;

                var lang;
                if (courseType === 'js' || courseType === 'javascript') {
                    lang = CM.javascript();
                } else if (courseType === 'css') {
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
                                if (footerStatus) footerStatus.textContent = 'Unsaved changes in the editor.';
                            }
                        }),
                    ],
                });

                syncPayloads(view);
                document.querySelectorAll('.challenge-footer button').forEach(function (button) { button.disabled = false; });

                /* A bare script tag in srcdoc is parsed as HTML and printed as
                   text, so the preview never ran. Wrap the student's source
                   in a document and mirror its console back to the results
                   tab. Sandboxed without allow-same-origin, so the frame's
                   origin is null: identify it by contentWindow, not origin. */
                function previewDocument(code) {
                    if (courseType !== 'js' && courseType !== 'javascript') {
                        return code;
                    }
                    var payload = JSON.stringify(code).replace(/</g, '\\u003c');
                    return '\x3c!doctype html>\x3chtml>\x3chead>\x3cmeta charset="utf-8">\x3c/head>\x3cbody>' +
                        '\x3cscr' + 'ipt>' +
                        'var count=0;var send=function(level,label,args){if(count++>=200)return;'  +
                        'var text=Array.from(args).map(function(a){try{return typeof a==="string"?a:(JSON.stringify(a) ?? String(a));}catch(e){return String(a);}}).join(" ").slice(0,4000);' +
                        'parent.postMessage({cq:1,level:level,label:label,text:text},"*");};' +
                        '["log","info","warn","error"].forEach(function(name){' +
                        'var original=console[name];' +
                        'console[name]=function(){send(name,name,arguments);' +
                        'if(original){original.apply(console,arguments);}};});' +
                        'window.addEventListener("error",function(e){send("error","ERROR",[e.message]);});' +
                        '(function(){try{' +
                        'var run=document.createElement("script");' +
                        'run.textContent=' + payload + ';' +
                        'document.body.appendChild(run);' +
                        '}catch(e){send("error","ERROR",[e && e.message ? e.message : String(e)]);' +
                        '}})();' +
                        '\x3c\/script>\x3c/body>\x3c/html>';
                }

                function runPreview() {
                    var code = view.state.doc.toString();
                    clearConsole();
                    if (workspace.dataset.results === 'collapsed') setPane(workspace, 'results', true);
                    if (window.matchMedia('(max-width: 1100px)').matches) selectMobileTab('results', false);
                    selectOutputTab(courseType === 'js' || courseType === 'javascript' ? 'console' : 'preview');
                    frame.srcdoc = previewDocument(code);
                    if (footerStatus) footerStatus.textContent = 'Preview refreshed from the editor.';
                }

                runBtn.addEventListener('click', runPreview);

                window.addEventListener('message', function (event) {
                    if (event.source !== frame.contentWindow) {
                        return;
                    }
                    var payload = event.data;
                    if (event.origin !== 'null' || !payload || payload.cq !== 1
                        || !['log', 'info', 'warn', 'error'].includes(payload.level)
                        || typeof payload.label !== 'string' || payload.label.length > 32
                        || typeof payload.text !== 'string' || payload.text.length > 4000) {
                        return;
                    }
                    appendConsole(payload.level, payload.label, payload.text);
                    if (payload.level === 'error') {
                        selectOutputTab('console');
                    }
                });

                // Initial preview reflects any restored draft.
                frame.srcdoc = previewDocument(view.state.doc.toString());

                window.addEventListener('pageshow', function () {
                    document.querySelectorAll('[data-idle-label]').forEach(function (button) {
                        button.textContent = button.dataset.idleLabel; button.disabled = false; button.removeAttribute('aria-busy');
                    });
                });

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
