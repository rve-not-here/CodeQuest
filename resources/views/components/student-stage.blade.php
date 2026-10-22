@props(['steps'])

{{-- A compact orientation rail: where this learning entry sits in the loop.
     It is not navigation; recorded states only. --}}
<ol class="atlas-stage" aria-label="Learning stage">
    @foreach ($steps as $step)
        <li
            data-state="{{ $step['state'] }}"
            @if ($step['state'] === 'current') aria-current="step" @endif
        >
            <span class="atlas-stage-mark" aria-hidden="true"></span>
            <span class="atlas-stage-copy">
                <span class="atlas-stage-label">{{ $step['label'] }}</span>
                @if (! empty($step['note']))
                    <span class="atlas-stage-note">{{ $step['note'] }}</span>
                @endif
            </span>
        </li>
    @endforeach
</ol>
