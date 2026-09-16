@extends('layouts.app', ['role' => $role, 'workspace' => true])

@section('title', $mission->title)

@section('content')
    <nav class="mb-3 flex flex-wrap items-center gap-3">
        <a href="{{ route('mission.show', $mission) }}" class="btn-ghost">← BACK TO LESSON</a>
        <span class="text-xs font-bold text-phosphor-dim uppercase tracking-wide">{{ $course?->name }}</span>
    </nav>

    @if ($completed)
        <x-status-message type="success" title="MISSION COMPLETE" dismissible>
            You have already passed this mission.
        </x-status-message>
    @endif
    @if (session('mission_success'))
        <x-status-message type="success" title="MISSION COMPLETE" dismissible>
            {{ session('mission_success')['message'] }}
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
    @if (session('draft_saved'))
        <x-status-message type="info" title="DRAFT SAVED">
            Your work was saved.
        </x-status-message>
    @endif

    <div class="challenge-workspace">
        <section class="panel challenge-pane">
            <header class="challenge-pane-header">Instructions</header>
            <div class="challenge-pane-body">
                <div class="space-y-3 font-body text-[15px] leading-snug text-ink">
                    <p><span class="text-phosphor">CONCEPT:</span> {{ $mission->title }}</p>
                    @if ($mission->description)
                        <p>{{ $mission->description }}</p>
                    @else
                        <p class="text-phosphor-dim">No briefing attached to this mission.</p>
                    @endif
                    <p class="text-phosphor">PASS: +{{ $mission->points }} XP</p>
                    <p>Write the code from scratch to satisfy the challenge.</p>
                    <p class="text-phosphor-dim">RUN renders a live preview. SUBMIT runs server-side grading.</p>
                    <p class="text-phosphor-dim">A wrong submission costs {{ $wrongPenalty }} XP.</p>
                </div>
            </div>
        </section>

        <section class="panel challenge-pane">
            <header class="challenge-pane-header">Terminal</header>
            <div class="challenge-pane-body editor-pane">
                <div id="editor-host" class="challenge-pane-editor"></div>
                <textarea name="terminal-code" id="editor-source" class="hidden" spellcheck="false">{{ old('code', $code) }}</textarea>

                <div class="challenge-toolbar">
                    <button type="button" id="run" class="btn-ghost">RUN</button>

                    <form method="POST" action="{{ route('mission.submit', $mission) }}" class="inline">
                        @csrf
                        <input type="hidden" name="code" class="code-payload">
                        <button type="submit" class="btn-primary">SUBMIT</button>
                    </form>

                    @if (! $completed)
                    <form method="POST" action="{{ route('mission.draft', $mission) }}" class="inline">
                        @csrf
                        <input type="hidden" name="code" class="code-payload">
                        <button type="submit" class="btn-ghost">SAVE DRAFT</button>
                    </form>
                    @endif

                    @if ($nextMissionId)
                    <a href="{{ route('mission.show', $nextMissionId) }}" class="btn-ghost ml-auto">NEXT MISSION →</a>
                    @endif
                </div>
            </div>
        </section>

        <section class="panel challenge-pane">
            <header class="challenge-pane-header">Preview</header>
            <div class="challenge-pane-body is-frame">
                <iframe
                    id="preview-frame"
                    sandbox="allow-scripts"
                    title="Live preview"
                ></iframe>
            </div>
        </section>
    </div>

    @if (! $completed && ($revealedHintCount < $totalHintCount || $totalHintCount === 0))
    <div class="flex flex-wrap items-center gap-3">
        @if ($totalHintCount > 0 && $revealedHintCount < $totalHintCount)
        <form method="POST" action="{{ route('mission.hint', $mission) }}" class="inline">
            @csrf
            <input type="hidden" name="code" class="code-payload">
            <button type="submit" class="btn-ghost">[ HINT {{ $revealedHintCount + 1 }} / {{ $totalHintCount }} ] {{ $nextHintCost }} XP</button>
        </form>
        @endif

        <form method="POST" action="{{ route('mission.reveal', $mission) }}" class="inline">
            @csrf
            <input type="hidden" name="code" class="code-payload">
            <button type="submit" class="btn-ghost">[ SHOW CORRECT CODE ] {{ $revealCost }} XP</button>
        </form>
    </div>
    @endif

    @if ($hints)
    <div class="space-y-2">
        @foreach ($hints as $index => $hintText)
            <x-status-message type="warning" title="HINT {{ $index + 1 }}">
                {{ $hintText }}
            </x-status-message>
        @endforeach
    </div>
    @endif

    @push('scripts')
    <script>
        (function () {
            var initial = JSON.parse('{!! json_encode(old('code', $code), JSON_HEX_APOS | JSON_HEX_QUOT) !!}');
            var host = document.getElementById('editor-host');
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

                // Initial preview reflects any restored draft.
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