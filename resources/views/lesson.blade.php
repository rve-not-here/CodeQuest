@extends('layouts.app', ['role' => $role])

@section('title', $mission->title)

@section('content')
    <div class="lesson-shell">
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
    @if (session('knowledge_check_info'))
        <x-status-message type="info" title="{{ session('knowledge_check_info')['title'] }}">
            {{ session('knowledge_check_info')['message'] }}
        </x-status-message>
    @endif
    @if (session('knowledge_check_error'))
        <x-status-message type="error" title="KNOWLEDGE CHECK UNAVAILABLE">
            {{ session('knowledge_check_error') }}
        </x-status-message>
    @endif

    <nav class="lesson-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('learning-path') }}">← Learning Path</a>
        @if ($course)
            <span aria-hidden="true">/</span>
            <span>{{ $course->name }}</span>
        @endif
        @if ($section)
            <span aria-hidden="true">/</span>
            <span>{{ $section->title }}</span>
        @endif
    </nav>

    <x-page-header :title="$mission->title" subtitle="Read the concept, inspect the pattern, then enter the focused Challenge Workspace.">
        <x-slot:actions>
            <x-badge tone="{{ $mission->difficulty === 'HARD' ? 'alert' : ($mission->difficulty === 'MEDIUM' ? 'amber' : 'cyan') }}">
                {{ $mission->difficulty }}
            </x-badge>
            <x-badge tone="phosphor">PASS: +{{ $mission->points }} XP</x-badge>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 space-y-4">
        <x-panel title="LEARNING OBJECTIVE">
            @if ($mission->description)
                <p class="lesson-copy">{{ $mission->description }}</p>
            @else
                <p class="lesson-copy">No briefing attached to this mission.</p>
            @endif
        </x-panel>

        @if ($mission->target_html)
            <x-panel title="EXPECTED OUTPUT">
                <p class="mb-3 text-xs font-bold text-phosphor-dim">THE PATTERN THIS CHALLENGE AIMS FOR.</p>
                <pre class="lesson-code-block">{{ $mission->target_html }}</pre>
            </x-panel>
        @endif

        @if ($mission->broken_code)
            <x-panel title="TRY IT YOURSELF — STARTER">
                <p class="mb-3 text-xs font-bold text-amber">
                    PRACTICE ZONE, NOT GRADED. LIVE EDITING AND RUNNING HAPPEN IN THE CHALLENGE WORKSPACE.
                </p>
                <pre class="lesson-code-block">{{ $mission->broken_code }}</pre>
            </x-panel>
        @endif

        <x-panel title="MISSION NOTES">
            <p class="lesson-copy">
                A wrong submission costs {{ $wrongPenalty }} XP. Earn the PASS reward by satisfying the Challenge's grading rules.
            </p>
        </x-panel>

        @if ($knowledgeChecks->isNotEmpty())
            <section class="knowledge-check-lesson-block" aria-labelledby="lesson-knowledge-check-title">
                <div class="knowledge-check-lesson-heading">
                    <div>
                        <p class="terminal-kicker text-cyan">CONCEPT VERIFICATION</p>
                        <h2 id="lesson-knowledge-check-title">Knowledge Check</h2>
                    </div>
                    <x-badge tone="cyan">FORMATIVE</x-badge>
                </div>
                <p class="lesson-copy">
                    Confirm the concept before applying it in code. Results guide review; they do not replace the Challenge or Boss Challenge.
                </p>

                <div class="knowledge-check-lesson-list">
                    @foreach ($knowledgeChecks as $summary)
                        @php
                            $check = $summary['check'];
                            $latestAttempt = $summary['latestAttempt'];
                        @endphp
                        <article class="knowledge-check-lesson-item">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3>{{ $check->title }}</h3>
                                    @if ($check->is_required)
                                        <x-badge tone="amber">REQUIRED</x-badge>
                                    @else
                                        <x-badge tone="dim">OPTIONAL</x-badge>
                                    @endif
                                </div>
                                <p>
                                    {{ $summary['questionCount'] }} {{ Str::plural('question', $summary['questionCount']) }}
                                    @if ($latestAttempt?->status === 'submitted')
                                        · Latest: {{ $latestAttempt->score }} / {{ $latestAttempt->total_questions }} ({{ $latestAttempt->percentage }}%)
                                    @elseif ($latestAttempt)
                                        · Attempt {{ $latestAttempt->attempt_number }} in progress
                                    @endif
                                </p>
                            </div>

                            @if ($latestAttempt)
                                <a
                                    href="{{ route('knowledge-check.show', [$mission, $check, $latestAttempt]) }}"
                                    class="btn-ghost"
                                >{{ $latestAttempt->status === 'submitted' ? 'REVIEW RESULT' : 'RESUME CHECK' }} →</a>
                            @else
                                <form method="POST" action="{{ route('knowledge-check.start', [$mission, $check]) }}">
                                    @csrf
                                    <button type="submit" class="btn-ghost">START CHECK →</button>
                                </form>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <div class="lesson-actions">
        <div>
            <p class="terminal-kicker text-phosphor">LESSON COMPLETE // READY FOR APPLICATION</p>
            <p class="mt-1 text-sm text-static">The next screen contains the editor, preview, and authoritative submission controls.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('learning-path') }}" class="btn-ghost">← BACK TO LEARNING PATH</a>
            @if ($outstandingRequiredCheck)
                <form method="POST" action="{{ route('knowledge-check.start', [$mission, $outstandingRequiredCheck]) }}">
                    @csrf
                    <button type="submit" class="btn-primary">START REQUIRED CHECK →</button>
                </form>
            @else
                <a href="{{ route('mission.challenge', $mission) }}" class="btn-primary">START CHALLENGE →</a>
            @endif
        </div>
    </div>
    </div>
@endsection
