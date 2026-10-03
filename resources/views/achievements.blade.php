@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Achievements')

@section('content')
    <div class="mx-auto w-full max-w-[1080px]">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
            <div>
                <p class="eyebrow">Learning record</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">Achievements</h1>
                <p class="mt-2 max-w-[64ch] text-sm leading-6 text-fg-muted">Milestones you have earned through challenges and Boss Challenges.</p>
            </div>
            <a href="{{ route('learning-path') }}" class="btn btn-secondary btn-sm">View Learning Path →</a>
        </header>

        <section class="panel mt-6 px-5 py-5 md:px-6" aria-labelledby="achievement-summary-title">
            <h2 id="achievement-summary-title" class="eyebrow">Your milestones</h2>
            <p class="mt-2 font-mono text-2xl font-semibold text-fg">{{ $earnedCount }}/{{ $totalCount }} UNLOCKED</p>
            <p class="mt-2 text-xs leading-5 text-fg-muted">Awards appear here when your learning record meets their criteria.</p>
        </section>

        @if ($catalog->isEmpty())
            <section class="panel mt-6 px-6 py-10 text-center" aria-labelledby="no-achievements-title">
                <h2 id="no-achievements-title" class="text-lg font-semibold">NO ACHIEVEMENTS</h2>
                <p class="mt-2 text-sm text-fg-muted">No achievement milestones are available yet.</p>
            </section>
        @else
            <section class="mt-8" aria-labelledby="milestones-title">
                <h2 id="milestones-title" class="mb-3 text-lg font-semibold">Milestones</h2>
                <ol class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    @foreach ($catalog as $row)
                        <li class="panel min-w-0 px-5 py-5 md:px-6" data-achievement-state="{{ $row['awarded'] ? 'unlocked' : 'discovered' }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="eyebrow">Achievement</p>
                                    <h3 class="mt-1 text-[15px] font-semibold text-balance">{{ $row['name'] }}</h3>
                                </div>
                                <span class="badge {{ $row['awarded'] ? 'badge-accent' : 'badge-neutral' }}">{{ $row['awarded'] ? 'UNLOCKED' : 'DISCOVERED' }}</span>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-fg-muted">{{ $row['description'] ?? 'Achievement description pending.' }}</p>
                            @if ($row['awarded'] && $row['unlocked_at'] !== null)
                                <p class="mt-4 border-t border-line pt-3 font-mono text-xs text-fg-subtle">EARNED {{ $row['unlocked_at']->format('Y-m-d H:i') }}</p>
                            @else
                                <p class="mt-4 border-t border-line pt-3 text-xs text-fg-subtle">Keep learning to unlock this achievement.</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
    </div>
@endsection
