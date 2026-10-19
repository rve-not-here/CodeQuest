@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Experiment: '.$mission->title)

@php
    $language = match ($course?->type) {
        'css' => 'CSS',
        'js', 'javascript' => 'JavaScript',
        default => 'HTML',
    };
@endphp

@section('content')
    <div class="atlas-practice">
        <div class="log-running">
            <span>
                @if ($course)
                    Log sheet {{ str_pad((string) $course->order_num, 3, '0', STR_PAD_LEFT) }} · {{ $course->name }}
                @else
                    Experiment
                @endif
            </span>
            <a href="{{ route('mission.show', $mission) }}" aria-label="Return to {{ $mission->title }} lesson">Lesson →</a>
        </div>

        <header class="atlas-reading-heading">
            <div class="atlas-lesson-entry">
                <span class="atlas-lesson-entry-label">Entry {{ str_pad((string) $mission->order_num, 3, '0', STR_PAD_LEFT) }} · Experiment</span>
                <span class="atlas-stamp atlas-stamp--ink">Not recorded</span>
            </div>
            <h1 class="atlas-reading-title">{{ $mission->title }}</h1>
            <p class="atlas-check-lede">Ungraded experiment. Try changes and preview the result. Experiments do not submit answers, award XP, or change your progress.</p>
            <dl class="log-facts atlas-practice-facts">
                <div>
                    <dt>Language</dt>
                    <dd class="is-numeric">{{ $language }}</dd>
                </div>
                <div>
                    <dt>Scoring</dt>
                    <dd>None</dd>
                </div>
            </dl>
        </header>

        @if ($mission->description)
            <section class="atlas-practice-context" aria-label="Example context"><p>{{ $mission->description }}</p></section>
        @endif

        <div class="atlas-practice-controls">
            <div class="atlas-practice-modes" aria-label="Practice views">
                <button type="button" id="practice-code" data-practice-mode="code" aria-pressed="true" aria-controls="practice-code-panel" class="btn btn-ghost">Code</button>
                <button type="button" id="practice-output" data-practice-mode="output" aria-pressed="false" aria-controls="practice-output-panel" class="btn btn-ghost">Preview</button>
            </div>
            <div class="atlas-practice-actions">
                <button type="button" id="experiment-run" class="btn btn-primary">Run preview <x-workspace-icon name="play" /></button>
                <button type="button" id="experiment-reset" class="btn btn-ghost">Reset example</button>
            </div>
        </div>
        <div id="practice-workspace" class="atlas-practice-workspace" data-mode="code">
            <section id="practice-code-panel" class="atlas-practice-code" aria-labelledby="experiment-editor-title">
                <header class="atlas-practice-heading"><h2 id="experiment-editor-title">Code</h2></header>
                <div id="editor-host"></div>
            </section>
            <section id="practice-output-panel" class="atlas-practice-output" aria-labelledby="experiment-preview-title">
                <header class="atlas-practice-heading"><h2 id="experiment-preview-title">Preview</h2></header>
                <iframe id="experiment-preview" title="Experiment preview" sandbox="{{ in_array($course?->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}"></iframe>
            </section>
        </div>
        <a href="{{ route('mission.show', $mission) }}" class="btn btn-secondary atlas-practice-return">Return to lesson <x-workspace-icon name="chevron-right" /></a>
    </div>
    @push('scripts')
    <script>
        (function () {
            var initial = {{ Illuminate\Support\Js::from($mission->broken_code ?? '') }};
            var type = {{ Illuminate\Support\Js::from($course?->type ?? 'html') }};
            function init(cq) {
                var CM = cq.CodeMirror;
                var language = type === 'js' || type === 'javascript' ? CM.javascript() : (type === 'css' ? CM.css() : CM.html());
                var editor = new CM.EditorView({
                    doc: initial,
                    parent: document.getElementById('editor-host'),
                    extensions: [CM.basicSetup, language, CM.foundry],
                });
                var preview = document.getElementById('experiment-preview');
                preview.sandbox.value = cq.previewSandbox(type, initial);
                function run() { preview.srcdoc = cq.previewDocument(editor.state.doc.toString(), type, initial); }
                document.getElementById('experiment-run').addEventListener('click', run);
                document.getElementById('experiment-reset').addEventListener('click', function () {
                    editor.dispatch({ changes: { from: 0, to: editor.state.doc.length, insert: initial } });
                    run();
                    showMode('code');
                    editor.focus();
                });
                var workspace = document.getElementById('practice-workspace');
                var codeButton = document.getElementById('practice-code');
                var outputButton = document.getElementById('practice-output');
                function showMode(mode) {
                    workspace.dataset.mode = mode;
                    codeButton.setAttribute('aria-pressed', String(mode === 'code'));
                    outputButton.setAttribute('aria-pressed', String(mode === 'output'));
                    editor.requestMeasure();
                }
                codeButton.addEventListener('click', function () { showMode('code'); });
                outputButton.addEventListener('click', function () { showMode('output'); });
                run();
            }
            if (window.CodeQuest && window.CodeQuest.CodeMirror) {
                init(window.CodeQuest);
            } else {
                window.addEventListener('cq:codemirror-ready', function () { init(window.CodeQuest); }, { once: true });
            }
        })();
    </script>
    @endpush
@endsection
