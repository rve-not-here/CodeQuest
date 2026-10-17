@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', $mission->title)

@section('content')
    <div class="atlas-lesson">
        <nav aria-label="Breadcrumb" class="atlas-reading-context">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('learning-path') }}" class="hover:text-fg">Learning Path</a></li>
                @if ($course)
                    <li aria-hidden="true">/</li>
                    <li class="text-fg-muted">{{ $course->name }}</li>
                @endif
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="text-fg-muted">{{ $mission->title }}</li>
            </ol>
        </nav>

        <header class="atlas-reading-heading">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="atlas-reading-title">{{ $mission->title }}</h1>
            </div>
            <p class="mt-2 max-w-[65ch] text-sm leading-6 text-fg-muted">Read the brief and inspect the starter, then apply what you learned in the coding challenge.</p>
            <div class="atlas-reading-meta">
                <span class="atlas-reading-metadata">{{ $mission->difficulty }}</span>
                <span class="atlas-reading-metadata">+{{ $mission->points }} XP on first pass</span>
                @if ($completed)
                    <span class="badge badge-accent">Completed</span>
                @endif
            </div>
        </header>

        @if (session('mission_success'))
            <div class="panel mb-5 border-accent-line bg-accent-soft px-4 py-3" role="status"><strong class="text-sm text-accent">Challenge complete</strong> <span class="text-sm text-fg-muted">{{ session('mission_success')['message'] }}</span></div>
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

        <div class="atlas-lesson-body">
            <div class="atlas-lesson-document">
                <section class="atlas-lesson-section" aria-labelledby="lesson-objective">
                    <div class="atlas-lesson-section-heading">
                        <div class="flex items-center gap-3 mt-1 flex-wrap">
                            <h2 id="lesson-objective" class="text-lg font-semibold">What you will build</h2>
                        </div>
                    </div>
                    <div class="atlas-lesson-prose">
                        @if ($mission->description)
                            <p class="whitespace-pre-line">{{ $mission->description }}</p>
                        @else
                            <p>No lesson brief has been added yet. Review the starter below, then open the coding challenge.</p>
                        @endif
                    </div>
                </section>

                @if ($mission->target_html)
                    <section class="atlas-lesson-section" aria-labelledby="lesson-output">
                        <div class="atlas-lesson-section-heading">
                            <div class="flex items-center gap-3 mt-1 flex-wrap">
                            <h2 id="lesson-output" class="text-lg font-semibold">Expected output</h2>
                        </div>
                            <p class="mt-1 text-sm text-fg-muted">Use this reference to understand the intended result.</p>
                        </div>
                        <div class="atlas-lesson-example">
                            <pre class="code-block overflow-x-auto" tabindex="0"><code>{{ $mission->target_html }}</code></pre>
                        </div>
                    </section>
                @endif

                @if ($mission->broken_code)
                    <section class="atlas-lesson-section" aria-labelledby="lesson-starter">
                        <div class="atlas-lesson-section-heading">
                            <div class="flex items-center gap-3 mt-1 flex-wrap">
                            <h2 id="lesson-starter" class="text-lg font-semibold">Start from this code</h2>
                        </div>
                            <p class="mt-1 text-sm text-fg-muted">This is a read-only example. The editor and preview are in the coding challenge.</p>
                        </div>
                        <div class="atlas-lesson-example">
                            <pre class="code-block overflow-x-auto" tabindex="0"><code>{{ $mission->broken_code }}</code></pre>
                        </div>
                    </section>
                @endif

                @if ($knowledgeChecks->isNotEmpty())
                    <section class="atlas-lesson-section" aria-labelledby="lesson-checks">
                        <div class="atlas-lesson-section-heading">
                            <h2 id="lesson-checks" class="mt-1 text-lg font-semibold">Knowledge Check</h2>
                            <p class="mt-1 text-sm leading-6 text-fg-muted">Your result guides review and does not replace the coding challenge.</p>
                        </div>
                        <div class="divide-y divide-line">
                            @foreach ($knowledgeChecks as $summary)
                                @php
                                    $check = $summary['check'];
                                    $latestAttempt = $summary['latestAttempt'];
                                @endphp
                                <article class="atlas-lesson-check">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-[15px] font-medium">{{ $check->title }}</h3>
                                            <span class="badge {{ $check->is_required ? 'badge-warning' : 'badge-neutral' }}">{{ $check->is_required ? 'Required' : 'Optional' }}</span>
                                        </div>
                                        <p class="mt-1 text-xs leading-5 text-fg-subtle">
                                            {{ $summary['questionCount'] }} {{ Str::plural('question', $summary['questionCount']) }}
                                            @if ($latestAttempt?->status === 'submitted')
                                                · Latest: {{ $latestAttempt->score }} / {{ $latestAttempt->total_questions }} ({{ $latestAttempt->percentage }}%)
                                            @elseif ($latestAttempt)
                                                · Attempt {{ $latestAttempt->attempt_number }} in progress
                                            @endif
                                        </p>
                                    </div>
                                    @if ($latestAttempt)
                                        <a href="{{ route('knowledge-check.show', [$mission, $check, $latestAttempt]) }}" class="btn btn-ghost btn-sm">{{ $latestAttempt->status === 'submitted' ? 'Review result' : 'Resume check' }} <x-workspace-icon name="chevron-right" /></a>
                                    @else
                                        <form method="POST" action="{{ route('knowledge-check.start', [$mission, $check]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-sm">Start check <x-workspace-icon name="chevron-right" /></button>
                                        </form>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <section class="atlas-lesson-next" aria-labelledby="lesson-next">
                <div class="atlas-lesson-next-heading">
                    <h2 id="lesson-next" class="mt-1 text-lg font-semibold">{{ $outstandingRequiredCheck ? 'Complete the required check' : 'Apply what you learned' }}</h2>
                    <p class="mt-2 text-sm leading-6 text-fg-muted">
                        {{ $outstandingRequiredCheck ? 'This check must be submitted before the coding challenge opens.' : 'Open the editor to run a preview and complete this challenge.' }}
                    </p>
                </div>
                <div class="atlas-lesson-next-actions">
                    <a href="{{ route('mission.experiment', $mission) }}" class="btn btn-secondary w-full">TRY IT YOURSELF <x-workspace-icon name="chevron-right" /></a>
                    @if ($outstandingRequiredCheck)
                        <form method="POST" action="{{ route('knowledge-check.start', [$mission, $outstandingRequiredCheck]) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-full">START REQUIRED CHECK <x-workspace-icon name="chevron-right" /></button>
                        </form>
                    @else
                        <a href="{{ route('mission.challenge', $mission) }}" class="btn btn-primary w-full">START CHALLENGE <x-workspace-icon name="chevron-right" /></a>
                    @endif
                    <dl class="space-y-2 border-t border-line pt-4 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-fg-subtle">First pass</dt><dd class="font-mono text-accent">+{{ $mission->points }} XP</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-fg-subtle">Wrong attempt</dt><dd class="font-mono text-fg-muted">−{{ $wrongPenalty }} XP</dd></div>
                    </dl>
                    <a href="{{ route('learning-path') }}" class="inline-flex min-h-11 items-center text-sm text-fg-muted hover:text-fg">← Back to Learning Path</a>
                </div>
            </section>
        </div>
    </div>
@endsection
