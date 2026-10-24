{{--
    Answer breakdown shared by the lesson result modal and its inline review.
    Only submitted attempts receive responses, so the answer key is never in
    the DOM before the check is scored.
--}}
@foreach ($responses as $index => $response)
    <li class="kc-answer" data-answer-correct="{{ $response['isCorrect'] ? 'true' : 'false' }}" style="--i: {{ $index }}">
        <div class="kc-answer-head">
            <span class="kc-answer-position">Question {{ $index + 1 }}</span>
            <span class="kc-answer-status {{ $response['isCorrect'] ? 'kc-answer-status--correct' : 'kc-answer-status--wrong' }}">
                <x-icon :name="$response['isCorrect'] ? 'check' : 'x'" size="12" />
                {{ $response['isCorrect'] ? 'Correct' : 'Wrong' }}
            </span>
        </div>
        <h3 class="kc-answer-prompt">{{ $response['prompt'] }}</h3>
        <dl class="kc-answer-options">
            <div>
                <dt>Your answer</dt>
                <dd>{{ $response['selected'] }}</dd>
            </div>
            @unless ($response['isCorrect'])
                <div>
                    <dt>Correct answer</dt>
                    <dd>{{ $response['correct'] }}</dd>
                </div>
            @endunless
        </dl>
        @if (! $response['isCorrect'] && $response['explanation'])
            <p class="kc-answer-explanation">{{ $response['explanation'] }}</p>
        @endif
    </li>
@endforeach
