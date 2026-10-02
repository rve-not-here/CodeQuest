@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', $mission->title)

@section('content')
    <div class="mx-auto w-full max-w-[1080px]">
        <nav aria-label="Breadcrumb" class="font-mono text-xs text-fg-subtle">
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

        <header class="mt-3 mb-7 border-b border-line pb-6">
            <p class="eyebrow">Mission lesson @if ($section) · {{ $section->title }} @endif</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">{{ $mission->title }}</h1>
            <p class="mt-2 max-w-[65ch] text-sm leading-6 text-fg-muted">Read the brief and inspect the starter, then apply what you learned in the coding challenge.</p>
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <span class="badge badge-neutral">{{ $mission->difficulty }}</span>
                <span class="badge badge-accent">+{{ $mission->points }} XP on first pass</span>
                @if ($completed)
                    <span class="badge badge-accent">Completed</span>
                @endif
            </div>
        </header>

        @if (session('mission_success'))
            <div class="panel mb-5 border-accent-line bg-accent-soft px-4 py-3" role="status"><strong class="text-sm text-accent">MISSION COMPLETE</strong> <span class="text-sm text-fg-muted">{{ session('mission_success')['message'] }}</span></div>
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

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_19rem] lg:gap-8">
            <div class="min-w-0 space-y-6">
                <section class="panel" aria-labelledby="lesson-objective">
                    <div class="border-b border-line px-5 py-3.5">
                        <p class="eyebrow text-accent">01 · Understand</p>
                        <h2 id="lesson-objective" class="mt-1 text-lg font-semibold">What you will build</h2>
                    </div>
                    <div class="px-5 py-5 text-[15px] leading-7 text-fg-muted">
                        @if ($mission->description)
                            <p class="whitespace-pre-line">{{ $mission->description }}</p>
                        @else
                            <p>No lesson brief has been added yet. Review the starter below, then open the coding challenge.</p>
                        @endif
                    </div>
                </section>

                @if ($mission->target_html)
                    <section class="panel" aria-labelledby="lesson-output">
                        <div class="border-b border-line px-5 py-3.5">
                            <p class="eyebrow text-info">02 · Inspect</p>
                            <h2 id="lesson-output" class="mt-1 text-lg font-semibold">Expected output</h2>
                            <p class="mt-1 text-sm text-fg-muted">Use this reference to understand the intended result.</p>
                        </div>
                        <div class="min-w-0 p-4 md:p-5">
                            <pre class="code-block overflow-x-auto"><code>{{ $mission->target_html }}</code></pre>
                        </div>
                    </section>
                @endif

                @if ($mission->broken_code)
                    <section class="panel" aria-labelledby="lesson-starter">
                        <div class="border-b border-line px-5 py-3.5">
                            <p class="eyebrow text-warning">Starter</p>
                            <h2 id="lesson-starter" class="mt-1 text-lg font-semibold">Start from this code</h2>
                            <p class="mt-1 text-sm text-fg-muted">This is a read-only example. The editor and preview are in the coding challenge.</p>
                        </div>
                        <div class="min-w-0 p-4 md:p-5">
                            <pre class="code-block overflow-x-auto"><code>{{ $mission->broken_code }}</code></pre>
                        </div>
                    </section>
                @endif

                @if ($knowledgeChecks->isNotEmpty())
                    <section class="panel" aria-labelledby="lesson-checks">
                        <div class="border-b border-line px-5 py-3.5">
                            <p class="eyebrow text-info">Check your understanding</p>
                            <h2 id="lesson-checks" class="mt-1 text-lg font-semibold">Knowledge Check</h2>
                            <p class="mt-1 text-sm leading-6 text-fg-muted">Your result guides review and does not replace the coding challenge.</p>
                        </div>
                        <div class="divide-y divide-line">
                            @foreach ($knowledgeChecks as $summary)
                                @php
                                    $check = $summary['check'];
                                    $latestAttempt = $summary['latestAttempt'];
                                @endphp
                                <article class="flex flex-wrap items-center gap-4 px-5 py-4 sm:flex-nowrap">
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
                                        <a href="{{ route('knowledge-check.show', [$mission, $check, $latestAttempt]) }}" class="btn btn-ghost btn-sm">{{ $latestAttempt->status === 'submitted' ? 'Review result' : 'Resume check' }} →</a>
                                    @else
                                        <form method="POST" action="{{ route('knowledge-check.start', [$mission, $check]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-sm">Start check →</button>
                                        </form>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="panel lg:sticky lg:top-20" aria-labelledby="lesson-next">
                <div class="border-b border-line px-5 py-4">
                    <p class="eyebrow text-accent">Next step</p>
                    <h2 id="lesson-next" class="mt-1 text-lg font-semibold">{{ $outstandingRequiredCheck ? 'Complete the required check' : 'Apply what you learned' }}</h2>
                    <p class="mt-2 text-sm leading-6 text-fg-muted">
                        {{ $outstandingRequiredCheck ? 'This check must be submitted before the coding challenge opens.' : 'Open the editor to run a preview and complete this mission.' }}
                    </p>
                </div>
                <div class="space-y-4 p-5">
                    <a href="{{ route('mission.experiment', $mission) }}" class="btn btn-secondary w-full">TRY IT YOURSELF →</a>
                    @if ($outstandingRequiredCheck)
                        <form method="POST" action="{{ route('knowledge-check.start', [$mission, $outstandingRequiredCheck]) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-full">START REQUIRED CHECK →</button>
                        </form>
                    @else
                        <a href="{{ route('mission.challenge', $mission) }}" class="btn btn-primary w-full">START CHALLENGE →</a>
                    @endif
                    <dl class="space-y-2 border-t border-line pt-4 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-fg-subtle">First pass</dt><dd class="font-mono text-accent">+{{ $mission->points }} XP</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-fg-subtle">Wrong attempt</dt><dd class="font-mono text-fg-muted">−{{ $wrongPenalty }} XP</dd></div>
                    </dl>
                    <a href="{{ route('learning-path') }}" class="inline-flex min-h-11 items-center text-sm text-fg-muted hover:text-fg">← Back to Learning Path</a>
                </div>
            </aside>
        </div>
    </div>
@endsection
