@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Experiment: '.$mission->title)

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 py-6 md:px-6">
        <a href="{{ route('mission.show', $mission) }}" class="btn btn-ghost">← BACK TO LESSON</a>
        <header>
            <p class="eyebrow text-accent">Ungraded experiment</p>
            <h1 class="mt-2 text-xl font-semibold">{{ $mission->title }}</h1>
            <p class="mt-2 text-sm text-fg-muted">Try changes and preview the result. Experiments do not submit answers, award XP, or change your progress.</p>
        </header>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <section class="panel min-w-0 p-4" aria-labelledby="experiment-editor-title">
                <h2 id="experiment-editor-title" class="mb-3 font-semibold">Editable example</h2>
                <div id="editor-host" class="min-h-80"></div>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="button" id="experiment-run" class="btn btn-secondary">RUN / PREVIEW</button>
                    <button type="button" id="experiment-reset" class="btn btn-ghost">RESET EXAMPLE</button>
                </div>
            </section>
            <section class="panel min-w-0 p-4" aria-labelledby="experiment-preview-title">
                <h2 id="experiment-preview-title" class="mb-3 font-semibold">Preview output</h2>
                <iframe id="experiment-preview" class="h-96 w-full bg-white" title="Experiment preview" sandbox="{{ in_array($course?->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}"></iframe>
            </section>
        </div>
        <a href="{{ route('mission.show', $mission) }}" class="btn btn-primary">RETURN TO LESSON →</a>
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
                    extensions: [CM.basicSetup, language, CM.oneDark],
                });
                var preview = document.getElementById('experiment-preview');
                function run() { preview.srcdoc = cq.previewDocument(editor.state.doc.toString(), type); }
                document.getElementById('experiment-run').addEventListener('click', run);
                document.getElementById('experiment-reset').addEventListener('click', function () {
                    editor.dispatch({ changes: { from: 0, to: editor.state.doc.length, insert: initial } });
                    run();
                    editor.focus();
                });
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
