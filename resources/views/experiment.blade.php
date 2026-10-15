@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Experiment: '.$mission->title)

@section('content')
    <div class="atlas-practice">
        <a href="{{ route('mission.show', $mission) }}" class="btn btn-ghost">← BACK TO LESSON</a>
        <header class="atlas-reading-heading">
            <h1 class="atlas-reading-title">{{ $mission->title }}</h1>
             <p class="text-xs text-fg-subtle mt-1">Ungraded experiment</p>
            <p class="mt-2 text-sm text-fg-muted">Try changes and preview the result. Experiments do not submit answers, award XP, or change your progress.</p>
        </header>
        @if ($mission->description)
            <section class="atlas-practice-context" aria-label="Example context"><p>{{ $mission->description }}</p></section>
        @endif
        <div class="atlas-practice-controls">
            <div class="atlas-practice-modes" aria-label="Practice views">
                <button type="button" id="practice-code" data-practice-mode="code" aria-pressed="true" aria-controls="practice-code-panel" class="btn btn-ghost">Code</button>
                <button type="button" id="practice-output" data-practice-mode="output" aria-pressed="false" aria-controls="practice-output-panel" class="btn btn-ghost">Output</button>
            </div>
            <button type="button" id="experiment-run" class="btn btn-primary">RUN / PREVIEW</button>
            <button type="button" id="experiment-reset" class="btn btn-ghost">RESET EXAMPLE</button>
        </div>
        <div id="practice-workspace" class="atlas-practice-workspace" data-mode="code">
            <section id="practice-code-panel" class="atlas-practice-code" aria-labelledby="experiment-editor-title">
                <h2 id="experiment-editor-title" class="mb-3 font-semibold">Editable example</h2>
                <div id="editor-host" class="min-h-80"></div>
            </section>
            <section id="practice-output-panel" class="atlas-practice-output" aria-labelledby="experiment-preview-title">
                <h2 id="experiment-preview-title" class="mb-3 font-semibold">Preview output</h2>
                <iframe id="experiment-preview" class="h-96 w-full bg-white" title="Experiment preview" sandbox="{{ in_array($course?->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}"></iframe>
            </section>
        </div>
        <a href="{{ route('mission.show', $mission) }}" class="btn btn-secondary atlas-practice-return">RETURN TO LESSON →</a>
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
