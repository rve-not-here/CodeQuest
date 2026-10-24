{{--
    The scored Knowledge Check summary. Server-scored; this dialog only
    presents the submitted snapshots and gates the challenge CTA on the pass
    mark. The native dialog supplies the modal layer, Esc and the focus trap.
--}}
@php
    $percentage = (int) ($attempt['percentage'] ?? 0);
    $passed = $percentage >= $passScore;
    $modalId = 'check-result-'.$check->id;
@endphp
<dialog
    class="kc-result-dialog"
    id="{{ $modalId }}"
    data-check-result-modal
    data-check-result-id="{{ $check->id }}"
    data-passed="{{ $passed ? 'true' : 'false' }}"
    @if (($openCheckResultId ?? null) === $check->id) data-auto-open="true" @endif
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $modalId }}-title"
    aria-describedby="{{ $modalId }}-announce"
>
    <div class="kc-result-panel">
        <header class="kc-result-head">
            <button type="button" class="kc-result-close" data-check-result-close aria-label="Close result summary">
                <x-icon name="x" size="18" />
            </button>
            <p class="kc-result-kicker">{{ $check->title }} · Result</p>
            <h2 class="kc-result-title" id="{{ $modalId }}-title">{{ $attempt['score'] }} of {{ $attempt['total_questions'] }} correct</h2>

            <div class="kc-result-meta">
                <span class="atlas-stamp {{ $passed ? 'atlas-stamp--grn' : 'atlas-stamp--amb' }}">
                    <x-icon :name="$passed ? 'check' : 'triangle-alert'" size="12" />
                    {{ $passed ? 'Passed' : 'Not passed' }}
                </span>
                <div class="kc-result-score" aria-hidden="true">
                    <strong>{{ $percentage }}%</strong>
                    <span>Pass score: {{ $passScore }}%</span>
                </div>
            </div>

            <div class="kc-result-meter" role="progressbar" aria-label="Knowledge Check score" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percentage }}" aria-valuetext="{{ $percentage }} percent">
                <span style="--kc-score: {{ $percentage }}%"></span>
            </div>

            <p class="kc-result-announce sr-only" id="{{ $modalId }}-announce" role="status" aria-live="polite">
                {{ $passed ? 'Passed' : 'Not passed' }}. {{ $attempt['score'] }} of {{ $attempt['total_questions'] }} correct, {{ $percentage }} percent. Pass score {{ $passScore }} percent.
            </p>
        </header>

        <div class="kc-result-body">
            <p class="kc-result-message">
                @if ($percentage === 100)
                    Perfect score — every answer was correct. The coding challenge is unlocked.
                @elseif ($passed)
                    You passed. Solid work; review the questions you missed below, then move into the challenge.
                @else
                    Not quite there yet. You need at least {{ $passScore }}% to unlock the coding challenge. Review the misses below and retry when ready.
                @endif
            </p>

            <h3 class="kc-result-breakdown-title">Question breakdown</h3>
            <ol class="kc-answer-list">
                @include('partials.knowledge-check-answers', ['responses' => $responses])
            </ol>
        </div>

        <footer class="kc-result-actions">
            @if ($passed)
                <a href="{{ route('mission.challenge', $mission) }}" class="btn btn-primary">Proceed to Coding Challenge <x-icon name="chevron-right" data-arrow size="16" /></a>
                <button type="button" class="btn btn-secondary" data-check-review-answers>Review Answers</button>
            @else
                <form method="POST" action="{{ route('mission.knowledge-check.retry', [$mission, $check]) }}" data-check-retry-form>
                    @csrf
                    <input type="hidden" name="focus" value="1">
                    <button type="submit" class="btn btn-primary">Retry Quiz <x-icon name="arrow-right" size="16" /></button>
                </form>
                <button type="button" class="btn btn-secondary" data-check-review-answers>Review Answers</button>
                <div class="kc-result-locked">
                    <button type="button" class="btn btn-primary" disabled aria-disabled="true" aria-describedby="{{ $modalId }}-locked">
                        Proceed to Coding Challenge
                    </button>
                    <p class="kc-result-helper" id="{{ $modalId }}-locked">Score at least {{ $passScore }}% to unlock the coding challenge.</p>
                </div>
            @endif
        </footer>
    </div>
</dialog>
