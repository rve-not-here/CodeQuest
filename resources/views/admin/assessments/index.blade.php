@extends('layouts.app', ['role' => $role])

@section('title', $course->name.' Boss Challenge')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Boss Challenge · {{ $course->name }}</span>
            <div class="atlas-records-running-links">
                <a href="{{ route('admin.courses.edit', $course) }}">← {{ $course->name }}</a>
                <a href="{{ route('admin.courses') }}">All courses →</a>
            </div>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Curriculum</span>
            <h1 class="atlas-records-title">Boss Challenge</h1>
            <p class="atlas-records-lede">{{ $course->name }} — the single Boss Challenge for this course. Locking or drafting seals the challenge without rewriting recorded attempts. Grading rules are view-only.</p>
            @if ($assessment !== null)
                <dl class="log-facts atlas-records-facts" aria-label="Boss Challenge record">
                    <div><dt>Status</dt><dd>{{ ucfirst($assessment->status) }}</dd></div>
                    <div><dt>Passing score</dt><dd class="is-numeric">{{ $assessment->passing_score }}%</dd></div>
                    <div><dt>Course</dt><dd>{{ $course->name }}</dd></div>
                </dl>
            @endif
        </header>

        @if (session('status'))
            <div class="atlas-records-alerts">
                <div class="panel border-success/40 bg-success-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-success">Saved</strong>
                    <span class="text-fg-muted">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <section class="atlas-record-group" aria-labelledby="boss-challenge-title">
            <div class="atlas-record-group-head">
                <h2 id="boss-challenge-title" class="atlas-record-group-title">Boss Challenge of {{ $course->name }}</h2>
            </div>

            @if ($assessment === null)
                <section class="atlas-records-empty" aria-labelledby="empty-boss-title">
                    <h3 id="empty-boss-title" class="atlas-records-empty-title">No Boss Challenge</h3>
                    <p class="atlas-records-empty-copy">No Boss Challenge is defined for this course.</p>
                </section>
            @else
                <article class="atlas-record-entry">
                    <h3 class="atlas-record-entry-title">
                        <a href="{{ route('admin.courses.assessment.edit', [$course, $assessment]) }}">{{ $assessment->title }}</a>
                    </h3>
                    <p class="atlas-record-entry-copy">{{ $assessment->description }}</p>
                    <div class="atlas-record-entry-actions">
                        <a href="{{ route('admin.courses.assessment.edit', [$course, $assessment]) }}" class="btn btn-primary">Edit Boss Challenge →</a>
                    </div>
                </article>
            @endif
        </section>
    </div>
@endsection
