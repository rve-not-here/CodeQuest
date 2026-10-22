@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Timeline')

@section('content')
    @php
        $marks = [
            'mission_completed' => ['check', 'pass'],
            'assessment_completed' => ['check', 'pass'],
            'assessment_passed' => ['check', 'pass'],
            'wrong_submission' => ['x', 'fail'],
            'assessment_failed' => ['x', 'fail'],
            'knowledge_check_completed' => ['circle-question-mark', 'info'],
            'section_completed' => ['flag', 'ink'],
            'hint_used' => ['lightbulb', 'note'],
            'solution_revealed' => ['eye', 'note'],
            'achievement_earned' => ['award', 'pass'],
        ];
    @endphp
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>XP {{ number_format($totalXp) }}</span>
        </div>

        <header class="atlas-records-heading">
            <h1 class="atlas-records-title">Learning timeline</h1>
            <p class="atlas-records-lede">Your challenges, Knowledge Checks, Boss Challenges, XP changes, and achievements in the order they happened.</p>
            @if (($eventTotal ?? $events->count()) > 0)
                <p class="atlas-record-group-note">
                    Showing {{ (($page - 1) * $perPage) + 1 }}–{{ min($eventTotal, $page * $perPage) }} of {{ $eventTotal }} entries.
                </p>
            @endif
        </header>

        @if ($events->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="no-events-title">
                <h2 id="no-events-title" class="atlas-records-empty-title">No learning events yet</h2>
                <p class="atlas-records-empty-copy">Completed activities, submissions, and Boss Challenge results are written here as they happen.</p>
                <a href="{{ route('missions') }}" class="btn btn-secondary">Browse challenges</a>
            </section>
        @else
            @foreach ($events->groupBy(fn ($event) => $event['at']->format('Y-m-d')) as $date => $dailyEvents)
                <section class="atlas-record-group" aria-labelledby="day-{{ $date }}">
                    <div class="atlas-record-group-head">
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <h2 id="day-{{ $date }}" class="atlas-record-group-title"><time datetime="{{ $date }}">{{ $dailyEvents->first()['at']->format('F j, Y') }}</time></h2>
                            <span class="atlas-record-group-figure">{{ $dailyEvents->count() }} {{ Str::plural('entry', $dailyEvents->count()) }}</span>
                        </div>
                    </div>
                    <ol class="atlas-timeline-list">
                        @foreach ($dailyEvents as $event)
                            @php([$mark, $tone] = $marks[$event['type']] ?? ['·', 'ink'])
                            <li class="atlas-timeline-event" data-event-type="{{ $event['type'] }}">
                                <span class="atlas-timeline-mark atlas-timeline-mark--{{ $tone }}" aria-hidden="true"><x-icon :name="$mark" size="14" /></span>
                                <div class="min-w-0">
                                    <p class="atlas-timeline-label">{{ $event['label'] }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-0.5 text-right max-sm:col-start-2 max-sm:flex-row max-sm:items-baseline max-sm:gap-x-2 max-sm:text-left">
                                    <time class="atlas-timeline-time" datetime="{{ $event['at']->toIso8601String() }}">{{ $event['at']->format('H:i') }}</time>
                                    @if ($event['pts'] !== null && $event['pts'] !== 0)
                                        <span class="atlas-timeline-points atlas-timeline-points--{{ $event['pts'] > 0 ? 'credit' : 'debit' }}">{{ $event['pts'] > 0 ? '+' : '' }}{{ $event['pts'] }} XP</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endforeach
        @endif

        @if ($lastPage > 1)
            <nav class="atlas-records-pager" aria-label="Timeline pages">
                <p class="atlas-records-pager-figure">Page {{ $page }} of {{ $lastPage }}</p>
                <div class="atlas-records-pager-actions">
                    @if ($page <= 1)
                        <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">← Newer</span>
                    @else
                        <a href="{{ route('timeline', ['page' => $page - 1]) }}" class="btn btn-secondary btn-sm">← Newer</a>
                    @endif
                    @if ($page >= $lastPage)
                        <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">Older →</span>
                    @else
                        <a href="{{ route('timeline', ['page' => $page + 1]) }}" class="btn btn-secondary btn-sm">Older →</a>
                    @endif
                </div>
            </nav>
        @endif
    </div>
@endsection
