@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', $assessment->title)

@section('content')
    <div class="mx-auto w-full max-w-[1180px]">
        <nav aria-label="Breadcrumb" class="font-mono text-xs text-fg-subtle">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('assessments') }}" class="hover:text-fg">Boss Challenges</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="text-fg-muted">{{ $course->name }}</li>
            </ol>
        </nav>

        <header class="mt-3 flex flex-wrap items-start justify-between gap-4 border-b border-line pb-6">
            <div class="min-w-0">
                <p class="eyebrow">{{ $course->name }} · Boss Challenge</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">{{ $assessment->title }}</h1>
                <p class="mt-2 max-w-[65ch] text-sm leading-6 text-fg-muted">{{ $assessment->description ?: 'Apply the skills from this course in one coding assessment.' }}</p>
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

        <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="min-w-0 space-y-6">
                <section class="panel" aria-labelledby="boss-briefing-title">
                    <div class="border-b border-line px-5 py-4">
                        <p class="eyebrow text-accent">Your task</p>
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

                @if ($canBegin)
                    <section class="panel px-5 py-5" aria-labelledby="boss-start-title">
                        <h2 id="boss-start-title" class="text-lg font-semibold">Ready to begin</h2>
                        <p class="mt-2 max-w-[62ch] text-sm leading-6 text-fg-muted">Start an attempt to open the editor. Your work is scored on the server after submission.</p>
                        <form method="POST" action="{{ route('assessment.start', $assessment) }}" class="mt-5">
                            @csrf
                            <button type="submit" class="btn btn-primary">INITIATE CHALLENGE</button>
                        </form>
                    </section>
                @elseif ($attempt?->status === 'submitted')
                    <section class="panel px-5 py-5" aria-labelledby="boss-pending-title" role="status">
                        <h2 id="boss-pending-title" class="text-lg font-semibold">Verification in progress</h2>
                        <p class="mt-2 text-sm text-fg-muted">Your submission is recorded. Resume verification of the saved code to obtain its result.</p>
                        <form method="POST" action="{{ route('assessment.submit', $assessment) }}" class="mt-5">
                            @csrf
                            <input type="hidden" name="code" value="{{ $code }}">
                            <button type="submit" class="btn btn-primary">RESUME VERIFICATION</button>
                        </form>
                    </section>
                @else
                    <section class="panel overflow-hidden" aria-labelledby="boss-editor-title">
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
                        <div id="editor-host" class="min-h-[380px] overflow-hidden bg-[#0b0e14]"></div>
                        <textarea name="terminal-code" id="editor-source" class="hidden" spellcheck="false">{{ old('code', $code) }}</textarea>
                        <div class="flex flex-wrap items-center gap-2 border-t border-line p-3">
                            <button type="button" id="run" class="btn btn-secondary">RUN</button>
                            @if ($canEdit)
                                <form method="POST" action="{{ route('assessment.submit', $assessment) }}">
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

                    <section class="panel overflow-hidden" aria-labelledby="boss-preview-title">
                        <h2 id="boss-preview-title" class="border-b border-line px-4 py-3 text-sm font-semibold">Preview</h2>
                        <iframe id="preview-frame" class="min-h-[380px] w-full bg-white" sandbox="{{ in_array($course->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}" title="Live preview"></iframe>
                    </section>
                @endif
            </div>

            <aside class="panel p-5 lg:sticky lg:top-20" aria-labelledby="boss-details-title">
                <p class="eyebrow text-accent">Assessment details</p>
                <h2 id="boss-details-title" class="mt-1 text-lg font-semibold">What to expect</h2>
                <dl class="mt-4 space-y-3 border-t border-line pt-4 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-fg-subtle">Passing score</dt><dd class="font-mono text-fg">{{ $assessment->passing_score }}%</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-fg-subtle">First pass</dt><dd class="font-mono text-accent">+{{ $reward }} XP</dd></div>
                </dl>
                <p class="mt-4 text-xs leading-5 text-fg-subtle">Preview runs in an isolated frame. The server checks the submitted code.</p>
                <a href="{{ route('assessments') }}" class="btn btn-ghost mt-5 w-full">ALL CHALLENGES ←</a>
            </aside>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            var host = document.getElementById('editor-host');

            if (! host) {
                return;
            }

            var initial = JSON.parse('{!! json_encode(old('code', $code), JSON_HEX_APOS | JSON_HEX_QUOT) !!}');
            var source = document.getElementById('editor-source');
            var runBtn = document.getElementById('run');
            var frame = document.getElementById('preview-frame');

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
                var type = '{{ $course->type ?? 'html' }}';
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
                            if (u.docChanged) syncPayloads(view);
                        }),
                    ],
                });

                syncPayloads(view);

                runBtn.addEventListener('click', function () {
                    frame.srcdoc = view.state.doc.toString();
                });

                frame.srcdoc = view.state.doc.toString();
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
