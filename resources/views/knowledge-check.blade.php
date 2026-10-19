@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', $knowledgeCheck->title)

@section('content')
    <div class="atlas-check">
        <div class="log-running">
            <span>
                @if ($course)
                    Log sheet {{ str_pad((string) $course->order_num, 3, '0', STR_PAD_LEFT) }} · {{ $course->name }}
                @else
                    Knowledge check
                @endif
            </span>
            <a href="{{ route('mission.show', $mission) }}" aria-label="Return to {{ $mission->title }} lesson">Lesson →</a>
        </div>

        <header class="atlas-reading-heading">
            <div class="atlas-lesson-entry">
                <span class="atlas-lesson-entry-label">Entry {{ str_pad((string) $mission->order_num, 3, '0', STR_PAD_LEFT) }} · Knowledge Check</span>
                @if ($attempt->status === 'submitted')
                    <span class="atlas-stamp atlas-stamp--vio">Recorded</span>
                @endif
            </div>
            <h1 class="atlas-reading-title">{{ $knowledgeCheck->title }}</h1>
            <p class="atlas-check-lede">{{ $knowledgeCheck->instructions ?: 'Answer each question, then submit your answers for feedback.' }}</p>
            <dl class="log-facts atlas-check-facts">
                <div>
                    <dt>Questions</dt>
                    <dd class="is-numeric">{{ $questions->count() }}</dd>
                </div>
                <div>
                    <dt>Attempt</dt>
                    <dd class="is-numeric">{{ $attempt->attempt_number }}</dd>
                </div>
                <div>
                    <dt>Type</dt>
                    <dd>{{ $knowledgeCheck->is_required ? 'Required' : 'Optional' }}</dd>
                </div>
            </dl>
            <p class="atlas-check-purpose">Formative practice. Your result guides review; it does not award XP or complete the coding challenge.</p>
        </header>

        @if ($errors->any())
            <div class="panel mb-5 border-danger/40 bg-danger-soft px-4 py-3 text-sm text-danger" role="alert">
                <strong>Answers not submitted.</strong> {{ $errors->first('answers') ?: $errors->first() }}
            </div>
        @endif

        @if ($attempt->status === 'submitted')
            <section class="atlas-check-result" data-check-completed-key="cq:check:{{ auth()->id() }}:{{ $attempt->id }}" aria-labelledby="knowledge-check-result-title">
                <header class="atlas-check-result-heading">
                    <div class="atlas-check-result-copy">
                        <p class="atlas-check-result-label">Attempt {{ $attempt->attempt_number }} · Result</p>
                        <h2 id="knowledge-check-result-title">{{ $attempt->score }} / {{ $attempt->total_questions }} correct</h2>
                        <p class="atlas-check-result-note">Attempt {{ $attempt->attempt_number }} is saved in your learning history. Review your answers below.</p>
                    </div>
                    <div class="atlas-check-result-score">
                        <strong aria-label="Score {{ $attempt->percentage }} percent">{{ $attempt->percentage }}%</strong>
                    </div>
                </header>
                <div class="atlas-check-result-actions">
                    @if ($nextCheck)
                        <form method="POST" action="{{ route('knowledge-check.start', [$mission, $nextCheck]) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Continue to next check <x-workspace-icon name="chevron-right" /></button>
                        </form>
                    @else
                        <a href="{{ route('mission.challenge', $mission) }}" class="btn btn-primary">Continue to challenge <x-workspace-icon name="chevron-right" /></a>
                    @endif
                    <form method="POST" action="{{ route('knowledge-check.retry', [$mission, $knowledgeCheck]) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost">Retry check</button>
                    </form>
                </div>
            </section>

            <section class="atlas-check-review-section" aria-labelledby="knowledge-check-review-title">
                <h2 id="knowledge-check-review-title" class="atlas-check-review-title">Answer review</h2>
                <ol class="atlas-check-review-list">
                    @foreach ($responses as $index => $response)
                        <li class="atlas-check-review">
                            <div class="atlas-check-review-head">
                                <span class="atlas-check-review-position">Question {{ $index + 1 }}</span>
                                <span class="atlas-stamp {{ $response['isCorrect'] ? 'atlas-stamp--grn' : 'atlas-stamp--amb' }}">{{ $response['isCorrect'] ? 'Correct' : 'Review needed' }}</span>
                            </div>
                            <h3>{{ $response['prompt'] }}</h3>
                            <dl class="atlas-check-review-answers">
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
                                <p class="atlas-check-explanation">{{ $response['explanation'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @else
            <form method="POST" action="{{ route('knowledge-check.submit', [$mission, $knowledgeCheck, $attempt]) }}" data-knowledge-check data-draft-key="cq:check:{{ auth()->id() }}:{{ $attempt->id }}">
                @csrf
                <div class="atlas-check-form">
                    <div class="atlas-check-questions">
                        @foreach ($questions as $index => $question)
                            <fieldset class="atlas-check-question" id="question-{{ $question['id'] }}" data-check-question tabindex="-1">
                                <legend>
                                    <span class="atlas-check-position">Question {{ $index + 1 }} of {{ $questions->count() }}</span>
                                    <span class="atlas-check-prompt">{{ $question['prompt'] }}</span>
                                </legend>
                                @if ($question['codeSnippet'])
                                    <pre class="log-listing atlas-check-code" tabindex="0"><code>{{ $question['codeSnippet'] }}</code></pre>
                                @endif
                                <div class="atlas-check-options">
                                    @foreach ($question['options'] as $option)
                                        <label class="atlas-check-option" for="answer-{{ $question['id'] }}-{{ $option['id'] }}">
                                            <input id="answer-{{ $question['id'] }}-{{ $option['id'] }}" type="radio" name="answers[{{ $question['id'] }}]" value="{{ $option['id'] }}" @checked((string) old('answers.'.$question['id']) === (string) $option['id'])>
                                            <span>{{ $option['text'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>
                    <section class="atlas-check-submit" aria-labelledby="knowledge-check-progress-title">
                        <h2 id="knowledge-check-progress-title">Submit this check</h2>
                        <p>Answer every question before submitting. Your answers are scored together.</p>
                        <div class="atlas-check-summary" data-check-summary hidden>
                            <p class="atlas-check-count" data-check-count aria-live="polite"></p>
                            <div class="atlas-check-progress" role="progressbar" aria-label="Answered questions" aria-valuemin="0" aria-valuemax="{{ $questions->count() }}" aria-valuenow="0" data-check-progress>
                                <span></span>
                            </div>
                            <p class="atlas-check-missing-label">Unanswered</p>
                            <ul class="atlas-check-missing" data-check-unanswered></ul>
                        </div>
                        <button type="submit" class="btn btn-primary atlas-check-submit-button">Submit answers <x-workspace-icon name="chevron-right" /></button>
                        <p class="atlas-check-note">Results and explanations appear after submission.</p>
                    </section>
                </div>
            </form>
        @endif
    </div>
@endsection
