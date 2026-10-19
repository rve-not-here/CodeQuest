@extends('layouts.app', ['role' => $role])

@section('title', 'Needs attention')

@section('content')
    @php
        $signalTotal = $students->sum(fn (array $row): int => count($row['signals']));
        $newestSignal = $students
            ->pluck('last_activity_at')
            ->filter()
            ->sortDesc()
            ->first();
    @endphp
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Attention queue · {{ $role === 'teacher' ? 'Classroom scope' : 'Platform scope' }}</span>
            <a href="{{ route('students') }}">Students →</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Attention</span>
            <h1 class="atlas-records-title">Needs attention</h1>
            <p class="atlas-records-lede">Each signal has a named threshold and supporting evidence. These reasons are not grades or a composite score.</p>
            <dl class="log-facts atlas-records-facts" aria-label="Attention scope">
                <div><dt>Students flagged</dt><dd class="is-numeric">{{ $students->count() }}</dd></div>
                <div><dt>Signals recorded</dt><dd class="is-numeric">{{ $signalTotal }}</dd></div>
                <div><dt>Scope</dt><dd>{{ $role === 'teacher' ? 'Classroom' : 'Platform' }}</dd></div>
                <div><dt>Newest signal</dt><dd>{{ $newestSignal !== null ? $newestSignal->diffForHumans() : '—' }}</dd></div>
            </dl>
        </header>

        @if ($students->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="empty-attention-title">
                <h2 id="empty-attention-title" class="atlas-records-empty-title">No signals</h2>
                <p class="atlas-records-empty-copy">No student currently triggers an attention signal in this scope.</p>
                <a href="{{ route('students') }}" class="btn btn-ghost">Student roster →</a>
            </section>
        @else
            <section class="atlas-record-group" aria-labelledby="attention-queue-title">
                <div class="atlas-record-group-head">
                    <h2 id="attention-queue-title" class="atlas-record-group-title">Attention queue</h2>
                    <p class="atlas-record-group-figure">{{ $students->count() }} {{ Str::plural('student', $students->count()) }}</p>
                </div>
                <ol class="atlas-records-list" aria-label="Students needing attention">
                    @foreach ($students as $row)
                        <li class="atlas-record-entry" data-signal="{{ $row['primary'] }}">
                            <div class="atlas-record-entry-head">
                                <h3 class="atlas-record-entry-title"><a href="{{ route('student-progress', $row['student']) }}">{{ $row['student']->username }}</a></h3>
                                <p class="atlas-record-entry-figure">{{ count($row['signals']) }} {{ Str::plural('signal', count($row['signals'])) }}</p>
                            </div>
                            <p class="atlas-record-meta">
                                @if ($row['current_course'])
                                    <span>Course · {{ $row['current_course']->name }}</span>
                                @endif
                                <span>Last activity · {{ $row['last_activity_at'] !== null ? $row['last_activity_at']->diffForHumans() : 'None recorded' }}</span>
                            </p>
                            <ul class="atlas-signal-list">
                                <li class="atlas-signal atlas-signal--primary">
                                    <span class="atlas-signal-label">Primary · {{ \App\Services\AttentionService::SIGNAL_LABELS[$row['primary']] }}</span>
                                    <span class="atlas-signal-copy">{{ $row['reasons'][$row['primary']] }}</span>
                                </li>
                                @foreach ($row['signals'] as $signal)
                                    @continue($signal === $row['primary'])
                                    <li class="atlas-signal">
                                        <span class="atlas-signal-label">{{ \App\Services\AttentionService::SIGNAL_LABELS[$signal] }}</span>
                                        <span class="atlas-signal-copy">{{ $row['reasons'][$signal] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="atlas-record-entry-actions">
                                <a href="{{ route('student-progress', $row['student']) }}" class="btn btn-secondary btn-sm">Review student →</a>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <p class="atlas-record-group-note">A signal names a threshold that was crossed and the evidence behind it. It is not a grade, a rank, or a composite score.</p>
            </section>
        @endif
    </div>
@endsection
