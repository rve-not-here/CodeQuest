@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', $knowledgeCheck->title)

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
                <li><a href="{{ route('mission.show', $mission) }}" class="hover:text-fg">{{ $mission->title }}</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="text-fg-muted">Knowledge Check</li>
            </ol>
        </nav>

        <header class="mt-3 mb-6 border-b border-line pb-6">
            <div class="flex flex-wrap items-start gap-3">
                <a href="{{ route('mission.show', $mission) }}" class="btn btn-ghost btn-sm shrink-0" aria-label="Return to {{ $mission->title }} lesson">← Return</a>
                <div class="min-w-0 flex-1">
                    <p class="eyebrow text-info">Knowledge Check @if ($section) · {{ $section->title }} @endif</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">{{ $knowledgeCheck->title }}</h1>
                    <p class="mt-2 max-w-[65ch] text-sm leading-6 text-fg-muted">{{ $knowledgeCheck->instructions ?: 'Answer each question, then submit your answers for feedback.' }}</p>
                </div>
            </div>
            <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 border-t border-line pt-4 font-mono text-xs text-fg-subtle">
                <div class="flex gap-1.5"><dt>Questions</dt><dd class="text-fg">{{ $questions->count() }}</dd></div>
                <div class="flex gap-1.5"><dt>Attempt</dt><dd class="text-fg">{{ $attempt->attempt_number }}</dd></div>
                <div class="flex gap-1.5"><dt>Type</dt><dd class="text-fg">{{ $knowledgeCheck->is_required ? 'Required' : 'Optional' }}</dd></div>
            </dl>
        </header>

        @if ($errors->any())
            <div class="panel mb-5 border-danger/40 bg-danger-soft px-4 py-3 text-sm text-danger" role="alert">
                <strong>Answers not submitted.</strong> {{ $errors->first('answers') ?: $errors->first() }}
            </div>
        @endif

        @if ($attempt->status === 'submitted')
            <section class="panel" aria-labelledby="knowledge-check-result-title">
                <div class="flex flex-wrap items-start justify-between gap-5 px-5 py-5 md:px-6">
                    <div class="min-w-0">
                        <p class="eyebrow text-accent">Check recorded</p>
                        <h2 id="knowledge-check-result-title" class="mt-1 text-xl font-semibold tracking-tight">{{ $attempt->score }} / {{ $attempt->total_questions }} correct</h2>
                        <p class="mt-2 text-sm leading-6 text-fg-muted">Attempt {{ $attempt->attempt_number }} is saved in your learning history. Review your answers below.</p>
                    </div>
                    <strong class="font-mono text-3xl font-semibold tabular-nums text-accent" aria-label="Score {{ $attempt->percentage }} percent">{{ $attempt->percentage }}%</strong>
                </div>
                <div class="flex flex-wrap items-center gap-2 border-t border-line px-5 py-4 md:px-6">
                    @if ($nextCheck)
                        <form method="POST" action="{{ route('knowledge-check.start', [$mission, $nextCheck]) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Continue to next check →</button>
                        </form>
                    @else
                        <a href="{{ route('mission.challenge', $mission) }}" class="btn btn-primary">Continue to challenge →</a>
                    @endif
                    <form method="POST" action="{{ route('knowledge-check.retry', [$mission, $knowledgeCheck]) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost">Retry check</button>
                    </form>
                </div>
            </section>

            <section class="mt-6" aria-labelledby="knowledge-check-review-title">
                <h2 id="knowledge-check-review-title" class="text-lg font-semibold">Answer review</h2>
                <ol class="mt-4 grid grid-cols-1 gap-4">
                    @foreach ($responses as $index => $response)
                        <li class="panel min-w-0 px-5 py-5 md:px-6">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="eyebrow">Question {{ $index + 1 }}</p>
                                <span class="badge {{ $response['isCorrect'] ? 'badge-accent' : 'badge-warning' }}">{{ $response['isCorrect'] ? 'Correct' : 'Review needed' }}</span>
                            </div>
                            <h3 class="mt-2 text-[15px] font-medium leading-6">{{ $response['prompt'] }}</h3>
                            <dl class="mt-4 grid grid-cols-1 gap-3 border-t border-line pt-4 text-sm sm:grid-cols-2">
                                <div><dt class="text-xs text-fg-subtle">Your answer</dt><dd class="mt-1 break-words text-fg">{{ $response['selected'] }}</dd></div>
                                @unless ($response['isCorrect'])
                                    <div><dt class="text-xs text-fg-subtle">Correct concept</dt><dd class="mt-1 break-words text-fg">{{ $response['correct'] }}</dd></div>
                                @endunless
                            </dl>
                            @if ($response['explanation'])
                                <p class="mt-4 border-t border-line pt-4 text-sm leading-6 text-fg-muted">{{ $response['explanation'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @else
            <form method="POST" action="{{ route('knowledge-check.submit', [$mission, $knowledgeCheck, $attempt]) }}">
                @csrf
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_15rem] lg:items-start">
                    <div class="grid min-w-0 grid-cols-1 gap-4">
                        @foreach ($questions as $index => $question)
                            <fieldset class="panel min-w-0 p-5 md:p-6">
                                <legend class="sr-only">Question {{ $index + 1 }} of {{ $questions->count() }}: {{ $question['prompt'] }}</legend>
                                <p class="eyebrow text-info" aria-hidden="true">Question {{ $index + 1 }} of {{ $questions->count() }}</p>
                                <p class="mt-2 text-[15px] font-medium leading-6">{{ $question['prompt'] }}</p>
                                @if ($question['codeSnippet'])
                                    <pre class="code-block mt-4 overflow-x-auto"><code>{{ $question['codeSnippet'] }}</code></pre>
                                @endif
                                <div class="mt-4 grid grid-cols-1 gap-2">
                                    @foreach ($question['options'] as $option)
                                        <label class="answer-option min-w-0" for="answer-{{ $question['id'] }}-{{ $option['id'] }}">
                                            <input id="answer-{{ $question['id'] }}-{{ $option['id'] }}" type="radio" name="answers[{{ $question['id'] }}]" value="{{ $option['id'] }}" @checked((string) old('answers.'.$question['id']) === (string) $option['id'])>
                                            <span class="min-w-0 break-words text-[13px]">{{ $option['text'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>
                    <aside class="panel p-5 lg:sticky lg:top-20" aria-labelledby="knowledge-check-progress-title">
                        <p class="eyebrow text-info">Your check</p>
                        <h2 id="knowledge-check-progress-title" class="mt-1 text-lg font-semibold">{{ $questions->count() }} questions</h2>
                        <p class="mt-2 text-sm leading-6 text-fg-muted">Answer every question before submitting. Your answers are scored together.</p>
                        <button type="submit" class="btn btn-primary mt-5 w-full">Submit answers →</button>
                        <p class="mt-3 text-xs leading-5 text-fg-subtle">Results and explanations appear after submission.</p>
                    </aside>
                </div>
            </form>
        @endif
    </div>
@endsection
