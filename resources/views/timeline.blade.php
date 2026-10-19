@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Timeline')

@section('content')
    @php
        $marks = [
            'mission_completed' => ['✓', 'pass'],
            'assessment_completed' => ['✓', 'pass'],
            'assessment_passed' => ['✓', 'pass'],
            'wrong_submission' => ['×', 'fail'],
            'assessment_failed' => ['×', 'fail'],
            'knowledge_check_completed' => ['?', 'info'],
            'section_completed' => ['·', 'ink'],
            'hint_used' => ['·', 'note'],
            'solution_revealed' => ['·', 'note'],
        ];
    @endphp
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Learning timeline · XP {{ number_format($totalXp) }}</span>
            <a href="{{ route('learning-path') }}">Learning path →</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Chronology</span>
            <h1 class="atlas-records-title">Learning timeline</h1>
            <p class="atlas-records-lede">Your challenges, Knowledge Checks, and Boss Challenges in the order they happened.</p>
        </header>

        @if ($events->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="no-events-title">
                <h2 id="no-events-title" class="atlas-records-empty-title">No learning events yet</h2>
                <p class="atlas-records-empty-copy">Completed activities, submissions, and Boss Challenge results are written here as they happen.</p>
            </section>
        @else
            @foreach ($events->groupBy(fn ($event) => $event['at']->format('Y-m-d')) as $date => $dailyEvents)
                <section class="atlas-record-group" aria-labelledby="day-{{ $date }}">
                    <div class="atlas-record-group-head">
                        <h2 id="day-{{ $date }}" class="atlas-record-group-title"><time datetime="{{ $date }}">{{ $dailyEvents->first()['at']->format('F j, Y') }}</time></h2>
                        <span class="atlas-record-group-figure">{{ $dailyEvents->count() }} {{ Str::plural('entry', $dailyEvents->count()) }}</span>
                    </div>
                    <ol class="atlas-timeline-list">
                        @foreach ($dailyEvents as $event)
                            @php([$mark, $tone] = $marks[$event['type']] ?? ['·', 'ink'])
                            <li class="atlas-timeline-event" data-event-type="{{ $event['type'] }}">
                                <span class="atlas-timeline-mark atlas-timeline-mark--{{ $tone }}" aria-hidden="true">{{ $mark }}</span>
                                <div class="min-w-0">
                                    <p class="atlas-timeline-label">{{ $event['label'] }}</p>
                                    <time class="atlas-timeline-time" datetime="{{ $event['at']->toIso8601String() }}">{{ $event['at']->format('M d, Y · H:i') }}</time>
                                </div>
                                @if ($event['pts'] !== null && $event['pts'] !== 0)
                                    <span class="atlas-timeline-points atlas-timeline-points--{{ $event['pts'] > 0 ? 'credit' : 'debit' }}">{{ $event['pts'] > 0 ? '+' : '' }}{{ $event['pts'] }} XP</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endforeach
        @endif
    </div>
@endsection
