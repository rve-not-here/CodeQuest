@extends('layouts.app', ['role' => $role])

@section('title', $knowledgeCheck->title)

@section('content')
    <div class="knowledge-check-shell">
        <nav class="lesson-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('mission.show', $mission) }}">← Back to Lesson</a>
            @if ($course)
                <span aria-hidden="true">/</span>
                <span>{{ $course->name }}</span>
            @endif
            @if ($section)
                <span aria-hidden="true">/</span>
                <span>{{ $section->title }}</span>
            @endif
        </nav>

        <header class="knowledge-check-header">
            <div>
                <p class="terminal-kicker text-cyan">FORMATIVE // CONCEPT CHECK</p>
                <h1>{{ $knowledgeCheck->title }}</h1>
                <p>{{ $knowledgeCheck->instructions ?: 'Answer each question, then submit the complete check for server scoring.' }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-badge tone="cyan">{{ $questions->count() }} {{ Str::plural('QUESTION', $questions->count()) }}</x-badge>
                <x-badge tone="{{ $knowledgeCheck->is_required ? 'amber' : 'dim' }}">
                    {{ $knowledgeCheck->is_required ? 'REQUIRED' : 'OPTIONAL' }}
                </x-badge>
                <x-badge tone="dim">ATTEMPT {{ $attempt->attempt_number }}</x-badge>
            </div>
        </header>

        @if ($errors->any())
            <x-status-message type="error" title="ANSWERS NOT SUBMITTED">
                {{ $errors->first('answers') }}
            </x-status-message>
        @endif

        @if ($attempt->status === 'submitted')
            <section class="knowledge-check-result" aria-labelledby="knowledge-check-result-title" aria-live="polite">
                <div class="knowledge-check-score">
                    <div>
                        <p class="terminal-kicker text-phosphor">CHECK RECORDED</p>
                        <h2 id="knowledge-check-result-title">{{ $attempt->score }} / {{ $attempt->total_questions }} correct</h2>
                        <p>Attempt {{ $attempt->attempt_number }} remains in your learning history.</p>
                    </div>
                    <strong aria-label="Score {{ $attempt->percentage }} percent">{{ $attempt->percentage }}%</strong>
                </div>

                <ol class="knowledge-check-feedback-list">
                    @foreach ($responses as $index => $response)
                        <li class="knowledge-check-feedback {{ $response['isCorrect'] ? 'knowledge-check-feedback--correct' : 'knowledge-check-feedback--incorrect' }}">
                            <div class="knowledge-check-feedback-heading">
                                <h3>Question {{ $index + 1 }}</h3>
                                <span>
                                    {{ $response['isCorrect'] ? 'CORRECT' : 'REVIEW NEEDED' }}
                                </span>
                            </div>
                            <p class="knowledge-check-feedback-prompt">{{ $response['prompt'] }}</p>
                            <dl>
                                <div>
                                    <dt>Your answer</dt>
                                    <dd>{{ $response['selected'] }}</dd>
                                </div>
                                @unless ($response['isCorrect'])
                                    <div>
                                        <dt>Correct concept</dt>
                                        <dd>{{ $response['correct'] }}</dd>
                                    </div>
                                @endunless
                            </dl>
                            @if ($response['explanation'])
                                <p class="knowledge-check-explanation">{{ $response['explanation'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>

                <div class="knowledge-check-result-actions">
                    <form method="POST" action="{{ route('knowledge-check.retry', [$mission, $knowledgeCheck]) }}">
                        @csrf
                        <button type="submit" class="btn-ghost">RETRY CHECK</button>
                    </form>

                    @if ($nextCheck)
                        <form method="POST" action="{{ route('knowledge-check.start', [$mission, $nextCheck]) }}">
                            @csrf
                            <button type="submit" class="btn-primary">CONTINUE TO NEXT CHECK →</button>
                        </form>
                    @else
                        <a href="{{ route('mission.challenge', $mission) }}" class="btn-primary">CONTINUE TO CHALLENGE →</a>
                    @endif
                </div>
            </section>
        @else
            <form
                method="POST"
                action="{{ route('knowledge-check.submit', [$mission, $knowledgeCheck, $attempt]) }}"
                class="knowledge-check-form"
            >
                @csrf

                <div class="knowledge-check-question-list">
                    @foreach ($questions as $index => $question)
                        <fieldset class="knowledge-check-question">
                            <legend>
                                <span>Question {{ $index + 1 }} of {{ $questions->count() }}</span>
                                {{ $question['prompt'] }}
                            </legend>

                            @if ($question['codeSnippet'])
                                <pre class="lesson-code-block"><code>{{ $question['codeSnippet'] }}</code></pre>
                            @endif

                            <div class="knowledge-check-options">
                                @foreach ($question['options'] as $option)
                                    <label class="knowledge-check-option" for="answer-{{ $question['id'] }}-{{ $option['id'] }}">
                                        <input
                                            id="answer-{{ $question['id'] }}-{{ $option['id'] }}"
                                            type="radio"
                                            name="answers[{{ $question['id'] }}]"
                                            value="{{ $option['id'] }}"
                                            @checked((string) old('answers.'.$question['id']) === (string) $option['id'])
                                        >
                                        <span>{{ $option['text'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>

                <div class="knowledge-check-submit-bar">
                    <p>All questions are scored together. Correct-answer metadata stays on the server until submission.</p>
                    <button type="submit" class="btn-primary">SUBMIT KNOWLEDGE CHECK →</button>
                </div>
            </form>
        @endif
    </div>
@endsection
