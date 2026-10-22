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
                    <span class="atlas-stamp atlas-stamp--vio"><x-icon name="clipboard-check" size="12" />Recorded</span>
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
                            <button type="submit" class="btn btn-primary">Continue to next check <x-icon name="chevron-right" data-arrow size="16" /></button>
                        </form>
                    @else
                        <a href="{{ route('mission.challenge', $mission) }}" class="btn btn-primary">Continue to challenge <x-icon name="chevron-right" data-arrow size="16" /></a>
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
                                <span class="atlas-stamp {{ $response['isCorrect'] ? 'atlas-stamp--grn' : 'atlas-stamp--amb' }}"><x-icon :name="$response['isCorrect'] ? 'check' : 'triangle-alert'" size="12" />{{ $response['isCorrect'] ? 'Correct' : 'Review needed' }}</span>
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
            @include('partials.knowledge-check-form', [
                'mission' => $mission,
                'knowledgeCheck' => $knowledgeCheck,
                'attemptId' => $attempt->id,
                'questions' => $questions,
                'returnTo' => null,
            ])
        @endif
    </div>
@endsection
