@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', $mission->title)

@section('content')
    @php
        $language = match ($course?->type) {
            'css' => 'CSS',
            'js', 'javascript' => 'JavaScript',
            default => 'HTML',
        };

        // The brief teaches; the last paragraph states the job. Lift the job out of
        // the prose so it can head the panel without being read twice.
        $paragraphs = preg_split('/\r\n\r\n|\n\n/', trim((string) $mission->description)) ?: [];
        $task = null;
        if ($paragraphs !== []) {
            $last = trim((string) end($paragraphs));
            if (str_starts_with($last, 'Your task:')) {
                $task = ucfirst(trim(substr($last, strlen('Your task:'))));
                array_pop($paragraphs);
            }
        }
    @endphp

    <div class="atlas-lesson">
        <header class="atlas-lesson-head">
            <div class="atlas-lesson-entry">
                <span class="atlas-lesson-entry-label">
                    Entry {{ str_pad((string) $mission->order_num, 3, '0', STR_PAD_LEFT) }} · Lesson
                    @if ($course) · Course {{ str_pad((string) $course->order_num, 2, '0', STR_PAD_LEFT) }} {{ $course->name }} @endif
                </span>
                @if ($completed)
                    <span class="atlas-stamp atlas-stamp--grn"><x-icon name="check" size="12" />Completed</span>
                @else
                    <span class="atlas-stamp atlas-stamp--ink"><x-icon name="minus" size="12" />Not recorded</span>
                @endif
            </div>

            <h1 class="atlas-lesson-title">{{ $mission->title }}</h1>

            @if ($task)
                <p class="atlas-lesson-task">{{ $task }}</p>
            @endif

            <dl class="log-facts atlas-lesson-facts">
                <div>
                    <dt>Difficulty</dt>
                    <dd>{{ ucfirst(strtolower((string) $mission->difficulty)) }}</dd>
                </div>
                <div>
                    <dt>Reward</dt>
                    <dd class="is-numeric">+{{ $mission->points }} XP</dd>
                </div>
                <div>
                    <dt>Wrong attempt</dt>
                    <dd class="is-numeric">−{{ $wrongPenalty }} XP</dd>
                </div>
                <div>
                    <dt>Section</dt>
                    <dd>{{ $section?->title ?? 'Course' }}</dd>
                </div>
            </dl>
        </header>

        @if (session('mission_success'))
            <div class="panel mb-5 border-success/40 bg-success-soft px-4 py-3" role="status"><strong class="text-sm text-success">Challenge complete</strong> <span class="text-sm text-fg-muted">{{ session('mission_success')['message'] }}</span></div>
        @endif
        @if (session('mission_info'))
            <div class="panel mb-5 px-4 py-3" role="status"><strong class="text-sm">{{ session('mission_info')['title'] }}.</strong> <span class="text-sm text-fg-muted">{{ session('mission_info')['message'] }}</span></div>
        @endif
        @if (session('mission_error'))
            <div class="panel mb-5 border-danger/40 bg-danger-soft px-4 py-3" role="alert"><strong class="text-sm text-danger">{{ session('mission_error')['title'] }}.</strong> <span class="text-sm text-fg-muted">{{ session('mission_error')['message'] }}</span></div>
        @endif
        @if (session('knowledge_check_info'))
            <div class="panel mb-5 border-info/40 bg-info-soft px-4 py-3" role="status"><strong class="text-sm">{{ session('knowledge_check_info')['title'] }}.</strong> <span class="text-sm text-fg-muted">{{ session('knowledge_check_info')['message'] }}</span></div>
        @endif
        @if (session('knowledge_check_error'))
            <div class="panel mb-5 border-danger/40 bg-danger-soft px-4 py-3" role="alert"><strong class="text-sm text-danger">Knowledge Check unavailable.</strong> <span class="text-sm text-fg-muted">{{ session('knowledge_check_error') }}</span></div>
        @endif

        <div class="atlas-lesson-grid">
            <div class="atlas-lesson-brief">
                @if ($paragraphs !== [])
                    <section class="atlas-lesson-section" aria-labelledby="lesson-objective">
                        <h2 id="lesson-objective">What you will build</h2>
                        <div class="atlas-lesson-prose">
                            @foreach ($paragraphs as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($mission->target_html)
                    <details class="atlas-lesson-reference">
                        <summary>
                            <span>Reference output</span>
                            <span>Target markup</span>
                        </summary>
                        <pre tabindex="0"><code>{{ $mission->target_html }}</code></pre>
                    </details>
                @endif

                @if ($knowledgeChecks->isNotEmpty())
                    <section class="atlas-lesson-section" aria-labelledby="lesson-checks">
                        <h2 id="lesson-checks">Knowledge Check</h2>
                        <p class="atlas-lesson-note">Your result guides review and does not replace the coding challenge.</p>
                        <div class="atlas-lesson-checks">
                            @foreach ($knowledgeChecks as $summary)
                                @php
                                    $check = $summary['check'];
                                    $latestAttempt = $summary['latestAttempt'];
                                @endphp
                                <article class="atlas-lesson-check">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="atlas-lesson-check-title">{{ $check->title }}</h3>
                                            <span class="atlas-stamp {{ $check->is_required ? 'atlas-stamp--amb' : 'atlas-stamp--ink' }}">{{ $check->is_required ? 'Required' : 'Optional' }}</span>
                                        </div>
                                        <p class="atlas-lesson-check-meta">
                                            {{ $summary['questionCount'] }} {{ Str::plural('question', $summary['questionCount']) }}
                                            @if ($latestAttempt?->status === 'submitted')
                                                · Latest: {{ $latestAttempt->score }} / {{ $latestAttempt->total_questions }} ({{ $latestAttempt->percentage }}%)
                                            @elseif ($latestAttempt)
                                                · Attempt {{ $latestAttempt->attempt_number }} in progress
                                            @endif
                                        </p>
                                    </div>
                                    @if ($latestAttempt)
                                        <a href="{{ route('knowledge-check.show', [$mission, $check, $latestAttempt]) }}" class="btn btn-ghost btn-sm">{{ $latestAttempt->status === 'submitted' ? 'Review result' : 'Resume check' }} <x-icon name="chevron-right" size="16" /></a>
                                    @else
                                        <form method="POST" action="{{ route('knowledge-check.start', [$mission, $check]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-sm">Start check <x-icon name="chevron-right" size="16" /></button>
                                        </form>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="atlas-lesson-bench" aria-label="Try it yourself" data-bench>
                <div class="atlas-bench">
                    <div class="atlas-bench-head">
                        <h2 class="atlas-bench-title">Try it yourself</h2>
                        <span class="atlas-stamp atlas-stamp--amb"><x-icon name="minus" size="12" />Ungraded</span>
                        <div class="atlas-bench-actions">
                            <button type="button" id="experiment-reset" class="btn btn-ghost">Reset starter</button>
                            <button type="button" id="experiment-run" class="btn btn-secondary">Run preview <x-icon name="play" size="16" /></button>
                        </div>
                    </div>

                    <div class="atlas-bench-modes" role="group" aria-label="Practice views">
                        <button type="button" id="practice-code" data-practice-mode="code" aria-pressed="true" aria-controls="practice-code-panel" class="btn btn-ghost">Code</button>
                        <button type="button" id="practice-output" data-practice-mode="output" aria-pressed="false" aria-controls="practice-output-panel" class="btn btn-ghost">Preview</button>
                    </div>

                    <div id="practice-workspace" class="atlas-bench-workspace" data-mode="code">
                        <section id="practice-code-panel" class="atlas-bench-pane atlas-bench-code" aria-labelledby="bench-code-title">
                            <header class="atlas-bench-pane-head">
                                <h3 id="bench-code-title">Code</h3>
                                <span>{{ $language }}</span>
                            </header>
                            <div id="editor-host" class="atlas-bench-editor"></div>
                        </section>
                        <section id="practice-output-panel" class="atlas-bench-pane atlas-bench-output" aria-labelledby="bench-preview-title">
                            <header class="atlas-bench-pane-head">
                                <h3 id="bench-preview-title">Preview</h3>
                            </header>
                            <div class="atlas-bench-frame">
                                <iframe id="experiment-preview" title="Try it yourself preview" sandbox="{{ in_array($course?->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}"></iframe>
                            </div>
                        </section>
                    </div>

                    <div class="atlas-bench-foot">
                        @if ($outstandingRequiredCheck)
                            <form method="POST" action="{{ route('knowledge-check.start', [$mission, $outstandingRequiredCheck]) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">Start required check <x-icon name="chevron-right" size="16" /></button>
                            </form>
                        @else
                            <a href="{{ route('mission.challenge', $mission) }}" class="btn btn-primary">Start challenge <x-icon name="chevron-right" size="16" /></a>
                        @endif
                        <p class="atlas-bench-note">Experiments are ungraded — nothing is submitted.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var initial = {{ Illuminate\Support\Js::from($mission->broken_code ?? '') }};
            var type = {{ Illuminate\Support\Js::from($course?->type ?? 'html') }};
            var editor = null;
            var preview = document.getElementById('experiment-preview');
            var frame = document.querySelector('.atlas-bench-frame');
            var workspace = document.getElementById('practice-workspace');
            var codeButton = document.getElementById('practice-code');
            var outputButton = document.getElementById('practice-output');
            var runButton = document.getElementById('experiment-run');
            var resetButton = document.getElementById('experiment-reset');

            function render() {
                var source = editor ? editor.state.doc.toString() : initial;
                preview.srcdoc = window.CodeQuest.previewDocument(source, type, initial);
                preview.sandbox.value = window.CodeQuest.previewSandbox(type, initial);
            }

            // The run presses the frame once, so a re-render is seen rather than
            // assumed. Reduced motion keeps the frame still.
            function press() {
                if (!frame || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) {
                    return;
                }
                frame.classList.remove('is-refreshed');
                void frame.offsetWidth;
                frame.classList.add('is-refreshed');
                frame.addEventListener('animationend', function () {
                    frame.classList.remove('is-refreshed');
                }, { once: true });
            }

            function showMode(mode) {
                workspace.dataset.mode = mode;
                codeButton.setAttribute('aria-pressed', String(mode === 'code'));
                outputButton.setAttribute('aria-pressed', String(mode === 'output'));
                if (editor) {
                    editor.requestMeasure();
                }
            }

            function mount(cq) {
                var CM = cq.CodeMirror;
                var language = type === 'js' || type === 'javascript' ? CM.javascript() : (type === 'css' ? CM.css() : CM.html());
                editor = new CM.EditorView({
                    doc: initial,
                    parent: document.getElementById('editor-host'),
                    extensions: [CM.basicSetup, language, CM.foundry],
                });
                render();
            }

            runButton.addEventListener('click', function () { render(); press(); });
            resetButton.addEventListener('click', function () {
                if (editor) {
                    editor.dispatch({ changes: { from: 0, to: editor.state.doc.length, insert: initial } });
                }
                render();
                showMode('code');
                press();
                if (editor) {
                    editor.focus();
                }
            });
            codeButton.addEventListener('click', function () { showMode('code'); });
            outputButton.addEventListener('click', function () { showMode('output'); });

            if (window.CodeQuest && window.CodeQuest.previewDocument) {
                render();
            } else {
                window.addEventListener('cq:preview-ready', function () { render(); }, { once: true });
            }

            if (window.CodeQuest && window.CodeQuest.CodeMirror) {
                mount(window.CodeQuest);
            } else {
                window.addEventListener('cq:codemirror-ready', function () { mount(window.CodeQuest); }, { once: true });
            }
        })();
    </script>
@endpush
