{{--
    The interactive Knowledge Check form.

    Shared by the standalone attempt screen and the lesson's own panel, so
    the question projection, the draft key and the submit target exist once.
    The answer key is never part of this form: only prompts, code snippets
    and option text reach the browser.

    Expects: $mission, $knowledgeCheck, $attemptId, $questions and an
    optional $action (the submit target — the lesson's panel posts back to
    itself so the check opens in place) and $returnTo.
--}}
<form method="POST" action="{{ $action ?? route('knowledge-check.submit', [$mission, $knowledgeCheck, $attemptId]) }}" data-knowledge-check data-draft-key="cq:check:{{ auth()->id() }}:{{ $attemptId }}">
    @csrf
    @if (($returnTo ?? null) !== null)
        <input type="hidden" name="return_to" value="{{ $returnTo }}">
    @endif
    <div class="atlas-check-form">
        <div class="atlas-check-questions">
            @foreach ($questions as $index => $question)
                <fieldset class="atlas-check-question" id="question-{{ $question['id'] }}" data-check-question tabindex="-1">
                    <legend>
                        <span class="atlas-check-position">Question {{ $index + 1 }} of {{ count($questions) }}</span>
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
        <section class="atlas-check-submit" aria-labelledby="atlas-check-progress-title">
            <h2 id="atlas-check-progress-title">Submit this check</h2>
            <p>Answer every question before submitting. Your answers are scored together.</p>
            <div class="atlas-check-summary" data-check-summary hidden>
                <p class="atlas-check-count" data-check-count aria-live="polite"></p>
                <div class="atlas-check-progress" role="progressbar" aria-label="Answered questions" aria-valuemin="0" aria-valuemax="{{ count($questions) }}" aria-valuenow="0" data-check-progress>
                    <span></span>
                </div>
                <p class="atlas-check-missing-label">Unanswered</p>
                <ul class="atlas-check-missing" data-check-unanswered></ul>
            </div>
            <button type="submit" class="btn btn-primary atlas-check-submit-button">Submit answers <x-icon name="chevron-right" data-arrow size="16" /></button>
            <p class="atlas-check-note">Results and explanations appear after submission.</p>
        </section>
    </div>
</form>
