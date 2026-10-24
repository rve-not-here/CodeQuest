@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', $mission->title)

@section('content')
    @php
        $language = match ($course?->type) {
            'css' => 'CSS',
            'js', 'javascript' => 'JavaScript',
            default => 'HTML',
        };

        // The task paragraph states the job; the last paragraph of the brief
        // states it. Lift the job out of the prose so it can head the panel
        // without being read twice.
        $paragraphs = preg_split('/\r\n\r\n|\n\n/', trim((string) $mission->description)) ?: [];
        $task = null;
        if ($paragraphs !== []) {
            $last = trim((string) end($paragraphs));
            if (str_starts_with($last, 'Your task:')) {
                $task = ucfirst(trim(substr($last, strlen('Your task:'))));
                array_pop($paragraphs);
            }
        }

        // The reference output is what the finished work should look like. In the
        // HTML course that is the markup the student writes; in the CSS and
        // JavaScript courses it is a rendered console panel the student's own
        // code has to produce, and its inline-style source is noise. A target
        // that carries its own presentation is always a result, never a source.
        $target = (string) $mission->target_html;
        $referenceIsMarkup = $course?->type === 'html'
            && ! preg_match('/\sstyle\s*=/i', $target);

        // A check that is open takes the reading column: the brief folds into
        // a disclosure so the form has the space it needs.
        $checkIsOpen = $knowledgeChecks->contains(
            fn (array $panel): bool => $panel['state'] === \App\Services\KnowledgeCheckService::STATE_IN_PROGRESS
        );

        // Every check that was started has been submitted. The heading can
        // then offer the way back to the docket, which sits below the fold
        // on a long lesson.
        $checkDone = $knowledgeChecks->isEmpty()
            || $knowledgeChecks->contains(fn (array $panel): bool => $panel['state'] === \App\Services\KnowledgeCheckService::STATE_COMPLETED);

        // The challenge CTA follows the same pass mark as the result dialog.
        // The server gate still clears on submission, so this rail must not
        // offer a second way around a failed check.
        $requiredCheckPanels = $knowledgeChecks->where('required', true);
        $failedRequiredCheckPanel = $requiredCheckPanels->first(
            fn (array $panel): bool => $panel['state'] === \App\Services\KnowledgeCheckService::STATE_COMPLETED
                && (int) ($panel['attempt']['percentage'] ?? 0) < $passScore
        );
        $requiredCheckFailed = $failedRequiredCheckPanel !== null;
    @endphp

    <div class="atlas-lesson">
        <header class="lesson-masthead">
            <div class="lesson-masthead-lead">
                <span class="atlas-lesson-entry-label">
                    Entry {{ str_pad((string) $mission->order_num, 3, '0', STR_PAD_LEFT) }} · Lesson
                    @if ($course) · Course {{ str_pad((string) $course->order_num, 2, '0', STR_PAD_LEFT) }} {{ $course->name }} @endif
                    @if ($section) · {{ $section->title }} @endif
                </span>

                <h1 class="atlas-lesson-title">@foreach (explode('_', $mission->title) as $part)@unless ($loop->first)_<wbr>@endunless{{ $part }}@endforeach</h1>

                @if ($task)
                    <p class="atlas-lesson-task">{{ $task }}</p>
                @endif
            </div>

            <div class="lesson-masthead-side">
                @if ($completed)
                    <span class="atlas-stamp atlas-stamp--grn"><x-icon name="check" size="12" />Completed</span>
                @else
                    <span class="atlas-stamp atlas-stamp--ink"><x-icon name="minus" size="12" />Not recorded</span>
                @endif
                <dl class="lesson-meta">
                    <div>
                        <dt>Difficulty</dt>
                        <dd>{{ ucfirst(strtolower($mission->difficulty)) }}</dd>
                    </div>
                    <div>
                        <dt>Reward</dt>
                        <dd class="is-numeric">+{{ $mission->points }} XP</dd>
                    </div>
                </dl>
            </div>
        </header>

        @if (session('mission_success') || session('mission_info') || session('mission_error') || session('knowledge_check_info') || session('knowledge_check_error'))
            <div class="lesson-alerts">
                @if (session('mission_success'))
                    <div class="panel border-success/40 bg-success-soft px-4 py-3" role="status"><strong class="text-sm text-success">Challenge complete</strong> <span class="text-sm text-fg-muted">{{ session('mission_success')['message'] }}</span></div>
                @endif
                @if (session('mission_info'))
                    <div class="panel px-4 py-3" role="status"><strong class="text-sm">{{ session('mission_info')['title'] }}.</strong> <span class="text-sm text-fg-muted">{{ session('mission_info')['message'] }}</span></div>
                @endif
                @if (session('mission_error'))
                    <div class="panel border-danger/40 bg-danger-soft px-4 py-3" role="alert"><strong class="text-sm text-danger">{{ session('mission_error')['title'] }}.</strong> <span class="text-sm text-fg-muted">{{ session('mission_error')['message'] }}</span></div>
                @endif
                @if (session('knowledge_check_info'))
                    <div class="panel border-info/40 bg-info-soft px-4 py-3" role="status"><strong class="text-sm">{{ session('knowledge_check_info')['title'] }}.</strong> <span class="text-sm text-fg-muted">{{ session('knowledge_check_info')['message'] }}</span></div>
                @endif
                @if (session('knowledge_check_error'))
                    <div class="panel border-danger/40 bg-danger-soft px-4 py-3" role="alert"><strong class="text-sm text-danger">Knowledge Check unavailable.</strong> <span class="text-sm text-fg-muted">{{ session('knowledge_check_error') }}</span></div>
                @endif
            </div>
        @endif

        @php
            $stageSteps = [
                [
                    'label' => 'Lesson',
                    'state' => ($outstandingRequiredCheck || $checkIsOpen || $checkDone || $completed) ? 'done' : 'current',
                ],
                [
                    'label' => 'Knowledge Check',
                    'state' => $completed || ($checkDone && ! $requiredCheckFailed) ? 'done' : (($outstandingRequiredCheck || $checkIsOpen || $requiredCheckFailed) ? 'current' : 'upcoming'),
                    'note' => $knowledgeChecks->isEmpty() ? 'Not required' : null,
                ],
                [
                    'label' => 'Challenge',
                    'state' => $completed ? 'done' : ($requiredCheckFailed || $checkIsOpen || $outstandingRequiredCheck ? 'upcoming' : 'current'),
                ],
            ];
        @endphp

        <div class="lesson-body">
            <aside class="lesson-rail" aria-label="Lesson progress and next step">
                <div class="lesson-rail-block">
                    <p class="atlas-lesson-entry-label">Progress</p>
                    <x-student-stage :steps="$stageSteps" />
                </div>

                <div class="lesson-rail-block">
                    <p class="atlas-lesson-entry-label">Next step</p>
                    <div class="lesson-rail-action">
                        @if ($completed)
                            <a href="{{ route('mission.challenge', $mission) }}" class="btn btn-primary">Review challenge <x-icon name="chevron-right" data-arrow size="16" /></a>
                        @elseif ($checkIsOpen)
                            <a href="#lesson-checks" class="btn btn-primary">Continue check <x-icon name="chevron-down" size="16" /></a>
                        @elseif ($outstandingRequiredCheck)
                            <form method="POST" action="{{ route('mission.knowledge-check.start', [$mission, $outstandingRequiredCheck]) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">Start required check <x-icon name="chevron-right" data-arrow size="16" /></button>
                            </form>
                        @elseif ($requiredCheckFailed)
                            <form method="POST" action="{{ route('mission.knowledge-check.retry', [$mission, $failedRequiredCheckPanel['check']]) }}">
                                @csrf
                                <input type="hidden" name="focus" value="1">
                                <button type="submit" class="btn btn-primary">Retry required check <x-icon name="arrow-right" size="16" /></button>
                            </form>
                        @else
                            <a href="{{ route('mission.challenge', $mission) }}" class="btn btn-primary">Start challenge <x-icon name="chevron-right" data-arrow size="16" /></a>
                        @endif
                    </div>

                    @if ($checkDone && $knowledgeChecks->isNotEmpty())
                        <a href="#lesson-checks" class="lesson-rail-secondary">Review check <x-icon name="chevron-down" size="13" /></a>
                    @endif

                    <p class="lesson-rail-note">
                        @if ($completed)
                            Lesson complete. The challenge stays available for practice.
                        @elseif ($checkIsOpen)
                            Your check is open. Finish it to unlock the challenge.
                        @elseif ($outstandingRequiredCheck)
                            A required check gates the coding challenge.
                        @elseif ($requiredCheckFailed)
                            You scored below {{ $passScore }}%. Retry the check to unlock the coding challenge.
                        @elseif ($knowledgeChecks->isNotEmpty())
                            Check passed. The coding challenge is open.
                        @else
                            The coding challenge is open.
                        @endif
                    </p>
                </div>

                <a href="{{ route('learning-path') }}" class="lesson-rail-link">Learning path <x-icon name="arrow-right" size="13" /></a>
            </aside>

            <div class="lesson-main">
                <div class="lesson-work">
                    <div class="atlas-lesson-brief">
                        @if ($paragraphs !== [])
                            @if ($checkIsOpen)
                                <details class="atlas-lesson-brief-fold" open>
                                    <summary>The brief</summary>
                                    @include('partials.lesson-objective', ['paragraphs' => $paragraphs])
                                </details>
                            @else
                                @include('partials.lesson-objective', ['paragraphs' => $paragraphs])
                            @endif
                        @endif

                        @if ($target !== '')
                            <details class="atlas-lesson-reference" open>
                                <summary>
                                    <span>Reference output</span>
                                    <span>Expected result</span>
                                </summary>
                                <div class="atlas-lesson-reference-part">
                                    <p class="atlas-lesson-reference-label">Result</p>
                                    <div class="atlas-lesson-reference-frame">
                                        <iframe id="reference-preview" title="Reference output" sandbox="{{ in_array($course?->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}"></iframe>
                                    </div>
                                </div>
                                @if ($referenceIsMarkup)
                                    <div class="atlas-lesson-reference-part">
                                        <p class="atlas-lesson-reference-label">Markup</p>
                                        <pre class="log-listing" tabindex="0" aria-label="Reference markup"><code>{{ $target }}</code></pre>
                                    </div>
                                @endif
                            </details>
                        @endif
                    </div>

                    <aside class="atlas-lesson-bench" aria-labelledby="practice-title" data-bench>
                        <div class="atlas-bench">
                            <div class="atlas-bench-head">
                                <h2 class="atlas-bench-title" id="practice-title">Try it yourself</h2>
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
                                <p class="atlas-bench-note">Ungraded practice — nothing is submitted.</p>
                            </div>
                        </div>
                    </aside>
                </div>

                @if ($knowledgeChecks->isNotEmpty())
                    <section class="lesson-checkpoint" id="lesson-checks" aria-label="Knowledge Check">
                        <header class="lesson-checkpoint-head">
                            <p class="atlas-lesson-entry-label">Checkpoint · Before the challenge</p>
                            <p class="lesson-checkpoint-note">Pass this check to open the coding challenge.</p>
                        </header>

                        @foreach ($knowledgeChecks as $panel)
                            @php
                                $check = $panel['check'];
                                $attempt = $panel['attempt'];
                                $checkState = $panel['state'];
                            @endphp
                            <section class="atlas-lesson-check-panel" data-check-state="{{ $checkState }}" aria-labelledby="lesson-check-{{ $check->id }}">
                                <header class="atlas-lesson-check-panel-head">
                                    <div class="atlas-lesson-check-panel-titles">
                                        <p class="atlas-lesson-entry-label">Knowledge Check</p>
                                        <h2 class="atlas-lesson-check-title" id="lesson-check-{{ $check->id }}">{{ $check->title }}</h2>
                                    </div>
                                    <div class="atlas-lesson-check-stamps">
                                        <span class="atlas-stamp {{ $panel['required'] ? 'atlas-stamp--amb' : 'atlas-stamp--ink' }}">{{ $panel['required'] ? 'Required' : 'Optional' }}</span>
                                        <span class="atlas-stamp {{ $checkState === \App\Services\KnowledgeCheckService::STATE_COMPLETED ? 'atlas-stamp--grn' : ($checkState === \App\Services\KnowledgeCheckService::STATE_IN_PROGRESS ? 'atlas-stamp--vio' : 'atlas-stamp--ink') }}">
                                            {{ $checkState === \App\Services\KnowledgeCheckService::STATE_COMPLETED ? 'Completed' : ($checkState === \App\Services\KnowledgeCheckService::STATE_IN_PROGRESS ? 'In progress' : 'Not started') }}
                                        </span>
                                    </div>
                                </header>
                                <p class="atlas-lesson-check-meta">
                                    {{ $panel['questionCount'] }} {{ Str::plural('question', $panel['questionCount']) }}
                                    @if ($checkState === \App\Services\KnowledgeCheckService::STATE_COMPLETED && $attempt !== null)
                                        · {{ $attempt['score'] }} / {{ $attempt['total_questions'] }} ({{ $attempt['percentage'] }}%)
                                    @elseif ($checkState === \App\Services\KnowledgeCheckService::STATE_IN_PROGRESS && $attempt !== null)
                                        · Attempt {{ $attempt['attempt_number'] }} in progress
                                    @endif
                                    @if ($panel['required'] && ! $completed && $checkState !== \App\Services\KnowledgeCheckService::STATE_COMPLETED)
                                        · Required before the challenge opens
                                    @endif
                                </p>

                                @if ($checkState === \App\Services\KnowledgeCheckService::STATE_COMPLETED && $attempt !== null)
                                    <div class="progress" role="progressbar" aria-label="Knowledge Check score" aria-valuemin="0" aria-valuemax="{{ $attempt['total_questions'] }}" aria-valuenow="{{ $attempt['score'] }}" aria-valuetext="{{ $attempt['score'] }} of {{ $attempt['total_questions'] }} correct">
                                        <span style="width: {{ $attempt['percentage'] }}%"></span>
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <p class="atlas-lesson-check-error" role="alert">{{ $errors->first('answers') ?: $errors->first() }}</p>
                                @endif

                                @if ($checkState === \App\Services\KnowledgeCheckService::STATE_NOT_STARTED)
                                    <form method="POST" action="{{ route('mission.knowledge-check.start', [$mission, $check]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">Start check <x-icon name="chevron-right" data-arrow size="16" /></button>
                                    </form>
                                @elseif ($checkState === \App\Services\KnowledgeCheckService::STATE_IN_PROGRESS)
                                    @include('partials.knowledge-check-form', [
                                        'mission' => $mission,
                                        'knowledgeCheck' => $check,
                                        'attemptId' => $attempt['id'],
                                        'questions' => $panel['questions'],
                                        'action' => route('mission.knowledge-check.submit', [$mission, $check, $attempt['id']]),
                                        'returnTo' => null,
                                    ])
                                @else
                                    @php
                                        $percentage = (int) ($attempt['percentage'] ?? 0);
                                        $passed = $percentage >= $passScore;
                                        $responses = $panel['responses'] ?? [];
                                    @endphp
                                    <div class="atlas-lesson-check-actions">
                                        <button type="button" class="btn btn-secondary" data-check-result-open data-check-result-target="check-result-{{ $check->id }}" aria-haspopup="dialog" aria-controls="check-result-{{ $check->id }}">Review result</button>
                                        <form method="POST" action="{{ route('mission.knowledge-check.retry', [$mission, $check]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost">Retry check</button>
                                        </form>
                                    </div>

                                    <details class="kc-inline-review" data-check-answer-review="{{ $check->id }}">
                                        <summary>Answer review <span>{{ count($responses) }} {{ Str::plural('question', count($responses)) }}</span></summary>
                                        <ol class="kc-answer-list">
                                            @include('partials.knowledge-check-answers', ['responses' => $responses])
                                        </ol>
                                    </details>

                                    @include('partials.knowledge-check-result-modal', [
                                        'mission' => $mission,
                                        'check' => $check,
                                        'attempt' => $attempt,
                                        'responses' => $responses,
                                        'passScore' => $passScore,
                                        'openCheckResultId' => $openCheckResultId ?? null,
                                    ])
                                @endif
                            </section>
                        @endforeach
                    </section>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var initial = {{ Illuminate\Support\Js::from($mission->broken_code ?? '') }};
            var type = {{ Illuminate\Support\Js::from($course?->type ?? 'html') }};
            var target = {{ Illuminate\Support\Js::from($target) }};
            var editor = null;
            var preview = document.getElementById('experiment-preview');
            var reference = document.getElementById('reference-preview');
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

            // The reference output is the finished work, shown as it will look —
            // not the inline styles that produced it.
            if (reference) {
                reference.srcdoc = target;
            }

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
