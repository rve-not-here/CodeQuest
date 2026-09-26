@extends('layouts.app', ['role' => $role])

@section('title', $assessment->title)

@section('content')
    <x-page-header
        title="{{ $assessment->title }}"
        subtitle="{{ $course->name }} · Boss Challenge"
        icon="◈"
    >
        <x-slot:actions>
            <x-badge tone="amber">PASS ≥ {{ $assessment->passing_score }}%</x-badge>
            <x-badge tone="phosphor">XP {{ $xpBalance }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if ($hasPassed)
        <x-status-message type="success" title="COURSE CLEARED">
            You have cleared this Boss Challenge
            {{ $attempt !== null && $attempt->status === 'failed' ? '— this retry failed, but your pass stands.' : '— later retries are practice and award no extra XP.' }}
        </x-status-message>
    @endif
    @if (session('assessment_success'))
        <x-status-message type="success" title="{{ session('assessment_success')['title'] }}">
            {{ session('assessment_success')['message'] }}
        </x-status-message>
    @endif
    @if (session('assessment_info'))
        <x-status-message type="info" title="{{ session('assessment_info')['title'] }}">
            {{ session('assessment_info')['message'] }}
        </x-status-message>
    @endif
    @if (session('assessment_error'))
        <x-status-message type="error" title="{{ session('assessment_error')['title'] }}">
            {{ session('assessment_error')['message'] }}
        </x-status-message>
    @endif
    @if ($errors->has('code'))
        <x-status-message type="error" title="CODE REQUIRED">
            Submit a code payload with the challenge.
        </x-status-message>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-panel title="Briefing">
            <div class="space-y-3 font-body text-[15px] leading-snug text-ink">
                <p><span class="text-phosphor">CONCEPT:</span> {{ $assessment->title }}</p>
                @if ($assessment->description)
                    <p>{{ $assessment->description }}</p>
                @else
                    <p class="text-phosphor-dim">No briefing attached to this challenge.</p>
                @endif
            </div>
        </x-panel>

        <x-panel title="Objective">
            <div class="space-y-3 font-body text-[15px] leading-snug text-ink">
                @if ($assessment->instructions)
                    <p>{{ $assessment->instructions }}</p>
                @else
                    <p class="text-phosphor-dim">No instructions attached to this challenge.</p>
                @endif
                <p class="text-phosphor-dim">PASS is scored at {{ $assessment->passing_score }}% — patterns are checked, never executed. A pass awards +{{ $reward }} XP once.</p>
            </div>
        </x-panel>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-panel title="Terminal">
        @if ($canBegin)
            <p class="font-body text-[15px] text-ink mb-3">
                No attempt is in progress. Initiate the challenge to open the terminal.
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('assessment.start', $assessment) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-primary">INITIATE CHALLENGE</button>
                </form>
                <a href="{{ route('assessments') }}" class="btn-ghost ml-auto">ALL CHALLENGES ←</a>
            </div>
        @elseif ($attempt?->status === 'submitted')
            <p class="font-body text-[15px] text-ink">Verification in progress — return to the challenge shortly.</p>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <a href="{{ route('assessments') }}" class="btn-ghost">ALL CHALLENGES ←</a>
            </div>
        @else
            @if (in_array($attempt?->status, ['passed', 'failed'], true))
                <x-status-message
                    type="{{ $attempt->status === 'passed' ? 'success' : 'error' }}"
                    title="{{ $attempt->status === 'passed' ? 'VERDICT: PASSED' : 'VERDICT: FAILED' }}"
                    class="mb-3"
                >
                    Scored {{ $attempt->score }}% — need {{ $assessment->passing_score }}% to clear. SUBMIT is closed; open a retry for a fresh attempt.
                </x-status-message>
            @endif

            <div id="editor-host" class="min-h-[320px] border border-phosphor-dim bg-void rounded-[2px]"></div>
            <textarea name="terminal-code" id="editor-source" class="hidden" spellcheck="false">{{ old('code', $code) }}</textarea>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                <button type="button" id="run" class="btn-ghost">RUN</button>

                @if ($canEdit)
                    <form method="POST" action="{{ route('assessment.submit', $assessment) }}" class="inline">
                        @csrf
                        <input type="hidden" name="code" class="code-payload">
                        <button type="submit" class="btn-primary">SUBMIT</button>
                    </form>
                @endif

                @if ($canRetry)
                    <form method="POST" action="{{ route('assessment.retry', $assessment) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn-primary">RETRY CHALLENGE</button>
                    </form>
                @endif

                <a href="{{ route('assessments') }}" class="btn-ghost ml-auto">ALL CHALLENGES ←</a>
            </div>
        @endif
    </x-panel>

        <x-panel title="Preview">
            <iframe
                id="preview-frame"
                class="w-full min-h-[320px] bg-white border border-phosphor-dim rounded-[2px]"
                sandbox="{{ in_array($course->type, ['js', 'javascript'], true) ? 'allow-scripts' : '' }}"
                title="Live preview"
            ></iframe>
        </x-panel>
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
