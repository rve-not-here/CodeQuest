@extends('layouts.app', ['role' => $role, 'workspace' => true, 'studentPrototype' => true])

@section('title', $assessment->title)

@section('content')
    @php
        $initialCode = old('code', $code);
        $initialCode = is_string($initialCode) ? $initialCode : ($initialCode === null ? '' : $code);
    @endphp
    <div class="atlas-boss">
        <nav aria-label="Breadcrumb" class="font-mono text-xs text-fg-subtle">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('assessments') }}" class="hover:text-fg">Boss Challenges</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="text-fg-muted">{{ $course->name }}</li>
            </ol>
        </nav>

        <header class="atlas-boss-heading">
            <div class="min-w-0">
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">{{ $assessment->title }}</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="badge {{ $hasPassed ? 'badge-accent' : ($attempt?->status === 'failed' ? 'badge-warning' : 'badge-neutral') }}">
                    {{ $hasPassed ? 'CLEARED' : ($attempt?->status === 'failed' ? 'FAILED' : ($canBegin ? 'READY' : ($canEdit ? 'IN PROGRESS' : 'AWAITING VERDICT'))) }}
                </span>
                <span class="badge badge-neutral">PASS ≥ {{ $assessment->passing_score }}%</span>
            </div>
        </header>

        <div class="mt-5 space-y-3">
            @if ($hasPassed)
                <div class="panel border-accent-line bg-accent-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-accent">COURSE CLEARED.</strong>
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
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert"><strong class="text-danger">CODE REQUIRED.</strong> <span class="text-fg-muted">Submit a code payload with the challenge.</span></div>
            @endif
        </div>

        <nav class="atlas-workbench-controls" aria-label="Workspace panes" data-workspace-controls>
            <button type="button" class="btn btn-ghost btn-sm" id="workspace-tab-brief" data-pane-toggle="brief" aria-controls="workspace-brief" aria-expanded="true">Task</button>
            <button type="button" class="btn btn-ghost btn-sm" id="workspace-tab-editor" data-pane-toggle="editor" aria-controls="workspace-editor" aria-expanded="true">Code</button>
            <button type="button" class="btn btn-ghost btn-sm" id="workspace-tab-preview" data-pane-toggle="preview" aria-controls="workspace-preview" aria-expanded="true">Output</button>
            <button type="button" class="btn btn-ghost btn-sm" data-workspace-reset>Reset panes</button>
        </nav>
        <div class="challenge-workspace atlas-boss-workspace" data-workspace>
            <section class="challenge-pane" id="workspace-brief" data-workspace-pane="brief" tabindex="0">
                <div class="challenge-pane-body">
                    <section class="atlas-boss-brief" aria-labelledby="boss-briefing-title">
                        <div class="border-b border-line px-5 py-4">
                            <h2 id="boss-briefing-title" class="mt-1 text-lg font-semibold">Briefing</h2>
                        </div>
                        <div class="space-y-4 px-5 py-5 text-[15px] leading-7 text-fg-muted">
                            @if ($assessment->description)
                                <p class="whitespace-pre-line">{{ $assessment->description }}</p>
                            @else
                                <p>No briefing has been added yet.</p>
                            @endif
                            @if ($assessment->instructions)
                                <div class="border-t border-line pt-4">
                                    <h3 class="text-sm font-semibold text-fg">Objective</h3>
                                    <p class="mt-2 whitespace-pre-line">{{ $assessment->instructions }}</p>
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="atlas-boss-details" aria-labelledby="boss-details-title">
                        <h2 id="boss-details-title" class="mt-1 text-lg font-semibold">What to expect</h2>
                        <dl class="mt-4 space-y-3 border-t border-line pt-4 text-sm">
                            <div class="flex justify-between gap-4"><dt class="text-fg-subtle">Passing score</dt><dd class="font-mono text-fg">{{ $assessment->passing_score }}%</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-fg-subtle">First pass</dt><dd class="font-mono text-accent">+{{ $reward }} XP</dd></div>
                        </dl>
                        <p class="mt-4 text-xs leading-5 text-fg-subtle">Preview runs in an isolated frame. The server checks the submitted code.</p>
                        <a href="{{ route('assessments') }}" class="btn btn-ghost mt-5 w-full">ALL CHALLENGES ←</a>
                    </section>
                </div>
            </section>
            <div class="workspace-separator" role="separator" tabindex="0" aria-label="Resize brief pane" aria-controls="workspace-brief" aria-orientation="vertical" aria-valuemin="15" aria-valuemax="35" aria-valuenow="25" data-workspace-resize="brief"></div>
            <section class="challenge-pane" id="workspace-editor" data-workspace-pane="editor" tabindex="0">
                @if ($canBegin)
                    <section class="atlas-boss-state" aria-labelledby="boss-start-title">
                        <h2 id="boss-start-title" class="text-lg font-semibold">Ready to begin</h2>
                        <p class="mt-2 max-w-[62ch] text-sm leading-6 text-fg-muted">Start an attempt to open the editor. Your work is scored on the server after submission.</p>
                        <form method="POST" action="{{ route('assessment.start', $assessment) }}" class="mt-5">
                            @csrf
                            <button type="submit" class="btn btn-primary">INITIATE CHALLENGE</button>
                        </form>
                    </section>
                @elseif ($attempt?->status === 'submitted')
                    <section class="atlas-boss-state" aria-labelledby="boss-pending-title" role="status">
                        <h2 id="boss-pending-title" class="text-lg font-semibold">Verification in progress</h2>
                        <p class="mt-2 text-sm text-fg-muted">Your submission is recorded. Resume verification of the saved code to obtain its result.</p>
                        <form method="POST" action="{{ route('assessment.submit', $assessment) }}" class="mt-5">
                            @csrf
                            <input type="hidden" name="code" value="{{ $code }}">
                            <button type="submit" class="btn btn-primary">RESUME VERIFICATION</button>
                        </form>
                    </section>
                @else
                    <section class="atlas-boss-code" aria-labelledby="boss-editor-title">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3">
                            <h2 id="boss-editor-title" class="text-sm font-semibold">Code editor</h2>
                            <span class="font-mono text-xs text-fg-subtle">{{ $canEdit ? 'Attempt in progress' : 'Review your code' }}</span>
                        </div>
                        @if (in_array($attempt?->status, ['passed', 'failed'], true))
                            <div class="border-b border-line px-4 py-3 text-sm" role="status">
                                <strong class="{{ $attempt->status === 'passed' ? 'text-accent' : 'text-danger' }}">{{ $attempt->status === 'passed' ? 'VERDICT: PASSED' : 'VERDICT: FAILED' }}.</strong>
                                <span class="text-fg-muted">Scored {{ $attempt->score }}% — need {{ $assessment->passing_score }}% to clear. SUBMIT is closed; open a retry for a fresh attempt.</span>
                            </div>
                        @endif
                        <p class="px-3 py-2 text-sm text-fg-muted">Build an HTML document. Include your CSS in a style element and JavaScript in a script element.</p>
                        <div id="editor-host" class="challenge-pane-editor"></div>
                        <textarea name="terminal-code" id="editor-source" class="hidden" spellcheck="false">{{ $initialCode }}</textarea>
                        <div class="flex flex-wrap items-center gap-2 border-t border-line p-3">
                            <button type="button" id="run" class="btn btn-secondary">RUN</button>
                            @if ($canEdit)
                                <form id="assessment-submit" method="POST" action="{{ route('assessment.submit', $assessment) }}">
                                    @csrf
                                    <input type="hidden" name="code" class="code-payload">
                                    <button type="submit" class="btn btn-primary">SUBMIT</button>
                                </form>
                            @endif
                            @if ($canRetry)
                                <form method="POST" action="{{ route('assessment.retry', $assessment) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">RETRY CHALLENGE</button>
                                </form>
                            @endif
                        </div>
                    </section>

                @endif
            </section>
            <div class="workspace-separator" role="separator" tabindex="0" aria-label="Resize preview pane" aria-controls="workspace-preview" aria-orientation="vertical" aria-valuemin="20" aria-valuemax="40" aria-valuenow="25" data-workspace-resize="preview"></div>
            <section class="challenge-pane" id="workspace-preview" data-workspace-pane="preview" tabindex="0">
                <p class="atlas-workbench-note">Run previews only. Submit records an assessment result.</p>
                @if (! $canBegin && $attempt?->status !== 'submitted')
                    <section class="atlas-boss-output" aria-labelledby="boss-preview-title">
                        <h2 id="boss-preview-title" class="border-b border-line px-4 py-3 text-sm font-semibold">Preview</h2>
                        <iframe id="preview-frame" class="atlas-boss-frame" sandbox="{{ in_array($course->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}" title="Live preview"></iframe>
                    </section>
                @else
                    <p class="p-5 text-sm text-fg-muted">Preview is available when an attempt is open for editing or review.</p>
                @endif
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
