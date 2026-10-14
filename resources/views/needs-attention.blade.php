@extends('layouts.app', ['role' => $role])

@section('title', 'Needs Attention')

@section('content')
    <div class="teacher-operations">
        <header class="teacher-context">
            <div>
                <h1>Needs Attention</h1>
                <p>{{ $role === 'teacher' ? 'Classroom scope' : 'SYSTEM-WIDE' }} · {{ $students->count() }} {{ Str::plural('student', $students->count()) }} with recorded signals.</p>
                <p>Each signal has a named threshold and supporting evidence. These reasons are not grades or a composite score.</p>
            </div>
            <a href="{{ route('students') }}" class="btn-ghost">Student roster →</a>
        </header>
        <div class="teacher-attention-records">
            @forelse ($students as $row)
                <article class="teacher-attention-record" aria-labelledby="attention-student-{{ $row['student']->id }}">
                    <header>
                        <h2 id="attention-student-{{ $row['student']->id }}"><a href="{{ route('student-progress', $row['student']) }}">{{ strtoupper($row['student']->username) }}</a></h2>
                        @if ($row['current_course'])
                            <p>{{ $row['current_course']->name }}</p>
                        @endif
                        <p class="teacher-state">{{ count($row['signals']) }} SIGNAL{{ count($row['signals']) > 1 ? 'S' : '' }}</p>
                    </header>
                    <div>
                        <p class="teacher-state text-warning">PRIMARY · {{ \App\Services\AttentionService::SIGNAL_LABELS[$row['primary']] }}</p>
                        <p>{{ $row['reasons'][$row['primary']] }}</p>
                        @if (count($row['signals']) > 1)
                            <details>
                                <summary>OTHER SIGNALS</summary>
                                <ul class="teacher-record-list">
                                    @foreach ($row['signals'] as $signal)
                                        @continue($signal === $row['primary'])
                                        <li><strong class="teacher-state">{{ \App\Services\AttentionService::SIGNAL_LABELS[$signal] }}</strong><span>{{ $row['reasons'][$signal] }}</span></li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                </article>
            @empty
                <section class="teacher-empty"><h2>No signals</h2><p>No student currently triggers an attention signal in this scope.</p></section>
            @endforelse
        </div>
    </div>
@endsection
