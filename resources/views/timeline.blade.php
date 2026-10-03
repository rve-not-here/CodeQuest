@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Timeline')

@section('content')
    <div class="mx-auto w-full max-w-[1080px]">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
            <div>
                <p class="eyebrow">Learning record</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">Learning Timeline</h1>
                <p class="mt-2 max-w-[64ch] text-sm leading-6 text-fg-muted">Your challenges, Knowledge Checks, and Boss Challenges in the order they happened.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="font-mono text-sm text-fg-muted">XP {{ number_format($totalXp) }}</span>
                <a href="{{ route('learning-path') }}" class="btn btn-secondary btn-sm">View Learning Path →</a>
            </div>
        </header>

        @if ($events->isEmpty())
            <section class="panel mt-6 px-6 py-10 text-center" aria-labelledby="no-events-title">
                <h2 id="no-events-title" class="text-lg font-semibold">NO LEARNING EVENTS</h2>
                <p class="mt-2 text-sm text-fg-muted">Your completed learning activities will appear here.</p>
            </section>
        @else
            <section class="mt-6" aria-labelledby="events-title">
                <h2 id="events-title" class="eyebrow mb-3">Recent learning activity</h2>
                <ol class="panel divide-y divide-line">
                    @foreach ($events as $event)
                        @php
                            [$marker, $markerTone] = match ($event['type']) {
                                'mission_completed', 'assessment_completed', 'assessment_passed' => ['✓', 'text-accent'],
                                'wrong_submission', 'assessment_failed' => ['×', 'text-danger'],
                                'knowledge_check_completed' => ['?', 'text-accent'],
                                'hint_used', 'solution_revealed', 'section_completed' => ['·', 'text-warning'],
                                default => ['·', 'text-fg-subtle'],
                            };
                        @endphp
                        <li class="flex min-w-0 flex-wrap items-start gap-3 px-5 py-4 md:flex-nowrap md:px-6" data-event-type="{{ $event['type'] }}">
                            <span class="grid size-7 shrink-0 place-items-center rounded-sm border border-line font-mono text-xs {{ $markerTone }}" aria-hidden="true">{{ $marker }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm leading-6 text-fg text-pretty">{{ $event['label'] }}</p>
                                <time class="mt-1 block font-mono text-xs text-fg-subtle" datetime="{{ $event['at']->toIso8601String() }}">{{ $event['at']->format('M d, Y · H:i') }}</time>
                            </div>
                            @if ($event['pts'] !== null && $event['pts'] !== 0)
                                <span class="badge {{ $event['pts'] > 0 ? 'badge-accent' : 'badge-warning' }}">{{ $event['pts'] > 0 ? '+' : '' }}{{ $event['pts'] }} XP</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
    </div>
@endsection
