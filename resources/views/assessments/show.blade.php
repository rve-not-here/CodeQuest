@extends('layouts.app', ['role' => $role, 'workspace' => true, 'studentPrototype' => true])

@section('title', $assessment->title)

@section('content')
    @php
        $initialCode = old('code', $code);
        $initialCode = is_string($initialCode) ? $initialCode : ($initialCode === null ? '' : $code);

        $status = $hasPassed
            ? ['label' => 'Cleared', 'stamp' => 'atlas-stamp--grn']
            : ($attempt?->status === 'failed'
                ? ['label' => 'Failed', 'stamp' => 'atlas-stamp--amb']
                : ($canBegin
                    ? ['label' => 'Ready', 'stamp' => 'atlas-stamp--acc']
                    : ($canEdit
                        ? ['label' => 'In progress', 'stamp' => 'atlas-stamp--vio']
                        : ['label' => 'Awaiting verdict', 'stamp' => 'atlas-stamp--vio'])));

        $hasAlerts = $hasPassed
            || session()->has('assessment_success')
            || session()->has('assessment_info')
            || session()->has('assessment_error')
            || $errors->has('code');
    @endphp
    <div class="atlas-boss">
        <div class="log-running atlas-boss-running">
            <span>Boss challenge · {{ $course->name }}</span>
            <a href="{{ route('assessments') }}">All Boss Challenges →</a>
        </div>

        <header class="atlas-boss-heading">
            <div class="atlas-boss-heading-main">
                <span class="atlas-lesson-entry-label">Boss challenge · {{ $course->name }}</span>
                <h1 class="atlas-boss-title">{{ $assessment->title }}</h1>
            </div>
            <div class="atlas-boss-heading-meta">
                <span class="atlas-stamp {{ $status['stamp'] }}">@if ($stampIcon = \App\Support\StampIcon::for($status['label']))<x-icon :name="$stampIcon" size="12" />@endif{{ $status['label'] }}</span>
                <p class="atlas-boss-meta">Pass {{ $assessment->passing_score }}% · First pass +{{ $reward }} XP</p>
            </div>
        </header>

        @if ($hasAlerts)
        <div class="atlas-boss-alerts">
            @if ($hasPassed)
                <div class="panel border-accent-line bg-accent-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-accent">Course cleared.</strong>
                    <span class="text-fg-muted">You have cleared this Boss Challenge{{ $attempt !== null && $attempt->status === 'failed' ? ' — this retry failed, but your pass stands.' : ' — later retries are practice and award no extra XP.' }}</span>
                </div>
            @endif
            @foreach (['assessment_success' => ['border-accent-line bg-accent-soft', 'text-accent'], 'assessment_info' => ['border-info/40 bg-info-soft', 'text-info'], 'assessment_error' => ['border-danger/40 bg-danger-soft', 'text-danger']] as $flashKey => $flashStyle)
                @if (session($flashKey))
                    <div class="panel {{ $flashStyle[0] }} px-4 py-3 text-sm" role="{{ $flashKey === 'assessment_error' ? 'alert' : 'status' }}">
                        <strong class="{{ $flashStyle[1] }}">{{ session($flashKey)['title'] }}.</strong>
                        <span class="text-fg-muted">{{ session($flashKey)['message'] }}</span>
                    </div>
                @endif
            @endforeach
            @if ($errors->has('code'))
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert"><strong class="text-danger">Code required.</strong> <span class="text-fg-muted">Submit a code payload with the challenge.</span></div>
            @endif
        </div>
        @endif

        <nav class="atlas-workbench-controls" aria-label="Workspace panes" data-workspace-controls>
            <button type="button" class="atlas-workbench-tab" id="workspace-tab-brief" data-pane-toggle="brief" aria-controls="workspace-brief" aria-expanded="true">Task</button>
            <button type="button" class="atlas-workbench-tab" id="workspace-tab-editor" data-pane-toggle="editor" aria-controls="workspace-editor" aria-expanded="true">Code</button>
            <button type="button" class="atlas-workbench-tab" id="workspace-tab-preview" data-pane-toggle="preview" aria-controls="workspace-preview" aria-expanded="true">Preview</button>
            <button type="button" class="atlas-workbench-tab atlas-workbench-reset" data-workspace-reset>Reset panes</button>
        </nav>
        <div class="challenge-workspace atlas-boss-workspace" data-workspace>
            <section class="challenge-pane" id="workspace-brief" data-workspace-pane="brief" tabindex="0">
                <div class="challenge-pane-body">
                    <section class="atlas-boss-brief" aria-labelledby="boss-briefing-title">
                        <div class="atlas-boss-brief-head">
                            <h2 id="boss-briefing-title" class="atlas-boss-brief-title">Briefing</h2>
                        </div>
                        <div class="atlas-boss-brief-copy">
                            @if ($assessment->description)
                                <p class="whitespace-pre-line">{{ $assessment->description }}</p>
                            @else
                                <p>No briefing has been added yet.</p>
                            @endif
                            @if ($assessment->instructions)
                                <div class="atlas-boss-objective">
                                    <h3>Objective</h3>
                                    <p class="whitespace-pre-line">{{ $assessment->instructions }}</p>
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="atlas-boss-details" aria-labelledby="boss-details-title">
                        <h2 id="boss-details-title" class="atlas-boss-details-title">What to expect</h2>
                        <dl class="log-facts atlas-boss-details-facts">
                            <div>
                                <dt>Passing score</dt>
                                <dd class="is-numeric">{{ $assessment->passing_score }}%</dd>
                            </div>
                            <div>
                                <dt>First pass</dt>
                                <dd class="is-numeric">+{{ $reward }} XP</dd>
                            </div>
                        </dl>
                        <p class="atlas-boss-note">Preview runs in an isolated frame. The server checks the submitted code.</p>
                        <a href="{{ route('assessments') }}" class="btn btn-ghost atlas-boss-back">All challenges</a>
                    </section>
                </div>
            </section>
            <div class="workspace-separator" role="separator" tabindex="0" aria-label="Resize brief pane" aria-controls="workspace-brief" aria-orientation="vertical" aria-valuemin="15" aria-valuemax="35" aria-valuenow="25" data-workspace-resize="brief"></div>
            <section class="challenge-pane" id="workspace-editor" data-workspace-pane="editor" tabindex="0">
                @if ($canBegin)
                    <section class="atlas-boss-state" aria-labelledby="boss-start-title">
                        <span class="atlas-lesson-entry-label">Boss challenge · {{ $course->name }}</span>
                        <h2 id="boss-start-title" class="atlas-boss-state-title">Ready to begin</h2>
                        <p class="atlas-boss-state-copy">Begin an attempt to open the editor. Your work is scored on the server after submission.</p>
                        <form method="POST" action="{{ route('assessment.start', $assessment) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Start attempt <x-icon name="chevron-right" size="16" /></button>
                        </form>
                    </section>
                @elseif ($attempt?->status === 'submitted')
                    <section class="atlas-boss-state" aria-labelledby="boss-pending-title" role="status">
                        <span class="atlas-lesson-entry-label">Boss challenge · {{ $course->name }}</span>
                        <h2 id="boss-pending-title" class="atlas-boss-state-title">Verification in progress</h2>
                        <p class="atlas-boss-state-copy">Your submission is recorded. Resume verification of the saved code to obtain its result.</p>
                        <form method="POST" action="{{ route('assessment.submit', $assessment) }}">
                            @csrf
                            <input type="hidden" name="code" value="{{ $code }}">
                            <button type="submit" class="btn btn-primary">Resume verification <x-icon name="chevron-right" size="16" /></button>
                        </form>
                    </section>
                @else
                    <section class="atlas-boss-code" aria-labelledby="boss-editor-title">
                        <div class="atlas-boss-code-head">
                            <h2 id="boss-editor-title" class="atlas-boss-code-title">Code editor</h2>
                            <span class="atlas-boss-code-state">{{ $canEdit ? 'Attempt in progress' : 'Review your code' }}</span>
                        </div>
                        @if (in_array($attempt?->status, ['passed', 'failed'], true))
                            <div class="atlas-boss-verdict" role="status" data-boss-verdict="{{ $attempt->status }}">
                                <span class="atlas-stamp {{ $attempt->status === 'passed' ? 'atlas-stamp--grn' : 'atlas-stamp--amb' }}"><x-icon :name="$attempt->status === 'passed' ? 'check' : 'x'" size="12" />{{ $attempt->status === 'passed' ? 'Passed' : 'Failed' }}</span>
                                <p>Scored {{ $attempt->score }}% — need {{ $assessment->passing_score }}% to clear. Submitting is closed; open a retry for a fresh attempt.</p>
                            </div>
                        @endif
                        <p class="atlas-boss-code-note">Build an HTML document. Include your CSS in a style element and JavaScript in a script element.</p>
                        <div id="editor-host" class="challenge-pane-editor"></div>
                        <textarea name="terminal-code" id="editor-source" class="hidden" spellcheck="false">{{ $initialCode }}</textarea>
                        <div class="atlas-boss-code-actions">
                            <button type="button" id="run" class="btn btn-secondary">Run preview <x-icon name="play" size="16" /></button>
                            @if ($canEdit)
                                <form id="assessment-submit" method="POST" action="{{ route('assessment.submit', $assessment) }}">
                                    @csrf
                                    <input type="hidden" name="code" class="code-payload">
                                    <button type="submit" class="btn btn-primary">Submit <x-icon name="send" size="16" /></button>
                                </form>
                            @endif
                            @if ($canRetry)
                                <form method="POST" action="{{ route('assessment.retry', $assessment) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">Retry challenge</button>
                                </form>
                            @endif
                        </div>
                    </section>

                @endif
            </section>
            <div class="workspace-separator" role="separator" tabindex="0" aria-label="Resize preview pane" aria-controls="workspace-preview" aria-orientation="vertical" aria-valuemin="20" aria-valuemax="40" aria-valuenow="25" data-workspace-resize="preview"></div>
            <section class="challenge-pane" id="workspace-preview" data-workspace-pane="preview" tabindex="0">
                <section class="atlas-boss-output" aria-labelledby="boss-preview-title">
                    <div class="atlas-boss-output-head">
                        <h2 id="boss-preview-title" class="atlas-boss-output-title">Preview</h2>
                        <span class="atlas-boss-output-note">Preview only. Nothing is recorded until the attempt is submitted.</span>
                    </div>
                    @if (! $canBegin && $attempt?->status !== 'submitted')
                        <iframe id="preview-frame" class="atlas-boss-frame" sandbox="{{ in_array($course->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}" title="Live preview"></iframe>
                    @else
                        <p class="atlas-boss-output-empty">Preview is available when an attempt is open for editing or review.</p>
                    @endif
                </section>
            </section>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            var host = document.getElementById('editor-host');

            if (! host) {
                return;
            }

            var initial = {{ Illuminate\Support\Js::from($initialCode) }};
            var readOnly = {{ Illuminate\Support\Js::from(! $canEdit) }};
            var source = document.getElementById('editor-source');
            var runBtn = document.getElementById('run');
            var frame = document.getElementById('preview-frame');

            var submitForm = document.getElementById('assessment-submit');
            if (submitForm) {
                submitForm.addEventListener('submit', function (event) {
                    if (! window.confirm('Submit this Boss Challenge for evaluation? This records the result of your current attempt.')) {
                        event.preventDefault();
                    }
                });
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

                var lang = CM.html();

                var view = new CM.EditorView({
                    doc: initial,
                    parent: host,
                    extensions: [
                        CM.basicSetup,
                        lang,
                        CM.foundry,
                        CM.EditorView.editable.of(! readOnly),
                        CM.EditorView.updateListener.of(function (u) {
                            if (u.docChanged) syncPayloads(view);
                        }),
                    ],
                });

                syncPayloads(view);

                runBtn.addEventListener('click', function () {
                    frame.srcdoc = cq.previewDocument(view.state.doc.toString(), 'html');
                    document.dispatchEvent(new CustomEvent('cq:workspace-pane', { detail: 'preview' }));
                });

                frame.srcdoc = cq.previewDocument(view.state.doc.toString(), 'html');
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
