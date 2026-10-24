@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Achievements')

@section('content')
    @php
        $percent = $totalCount > 0 ? (int) round($earnedCount / $totalCount * 100) : 0;
    @endphp
    <div class="atlas-records">
        <header class="atlas-records-heading">
            <h1 class="atlas-records-title">Achievements</h1>
            <p class="atlas-records-lede">Milestones recorded for your account, earned or still locked.</p>
        </header>

        <section class="atlas-records-summary" aria-labelledby="achievement-summary-title">
            <h2 id="achievement-summary-title" class="atlas-lesson-entry-label">Milestones unlocked</h2>
            <p class="atlas-records-figure">{{ $earnedCount }}/{{ $totalCount }}</p>
            <div class="progress atlas-records-summary-bar" role="progressbar" aria-label="Achievements unlocked" aria-valuemin="0" aria-valuemax="{{ max($totalCount, 1) }}" aria-valuenow="{{ $earnedCount }}" aria-valuetext="{{ $earnedCount }} of {{ $totalCount }} milestones unlocked">
                <span style="width: {{ $percent }}%"></span>
            </div>
            <p class="atlas-records-summary-note">Awards are recorded automatically when your learning record meets their criteria.</p>
        </section>

        @if ($catalog->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="no-achievements-title">
                <h2 id="no-achievements-title" class="atlas-records-empty-title">No achievements yet</h2>
                <p class="atlas-records-empty-copy">No achievement milestones are available yet. They appear once Command publishes them.</p>
                @if ($role === 'student')
                    <p class="game-copy-line">{{ \App\Support\GameCopy::line('empty.achievements') }}</p>
                @endif
                <a href="{{ route('learning-path') }}" class="btn btn-secondary">Open learning path <span aria-hidden="true">→</span></a>

            </section>
        @else
            <h2 class="atlas-records-subheading">Milestones</h2>
            <ol class="atlas-records-list">
                @foreach ($catalog as $row)
                    <li class="atlas-record-entry atlas-record-entry--compact" data-achievement-state="{{ $row['awarded'] ? 'unlocked' : 'discovered' }}">
                        <div class="atlas-record-entry-row atlas-record-entry-row--tag">
                            <h3 class="atlas-record-entry-title">{{ $row['name'] }}</h3>
                            @if ($row['awarded'])
                                <span class="atlas-stamp atlas-stamp--grn"><x-icon name="lock-open" size="12" />Unlocked</span>
                            @else
                                <span class="atlas-stamp atlas-stamp--ink"><x-icon name="lock" size="12" />Locked</span>
                            @endif
                        </div>
                        <p class="atlas-record-entry-copy">{{ $row['description'] ?? 'Achievement description pending.' }}</p>
                        @if ($row['awarded'] && $row['unlocked_at'] !== null)
                            <p class="atlas-record-meta">Earned {{ $row['unlocked_at']->format('M d, Y · H:i') }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
            @if ($earnedCount === 0)
                <div class="atlas-records-toolbar">
                    <p class="atlas-records-subheading">Next milestone</p>
                    <a href="{{ route('learning-path') }}" class="btn btn-ghost btn-sm">Open learning path <span aria-hidden="true">→</span></a>
                </div>
            @endif
        @endif
    </div>
@endsection
