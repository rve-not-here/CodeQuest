@extends('layouts.app', ['role' => $role])

@section('title', $course->name.' Boss Challenge')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · {{ $course->name }}</span>
            <a href="{{ route('admin.courses') }}">All courses →</a>
        </div>

    @include('admin.courses._nav', ['course' => $course, 'active' => 'assessment'])

        <h1 class="sr-only">Boss Challenge</h1>

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
                <h2 id="boss-challenge-title" class="atlas-record-group-title">Assessment record</h2>
            </div>

            @if ($assessment === null)
                <section class="atlas-records-empty" aria-labelledby="empty-boss-title">
                    <h3 id="empty-boss-title" class="atlas-records-empty-title">No Boss Challenge</h3>
                    <p class="atlas-records-empty-copy">No Boss Challenge is defined for this course.</p>
                </section>
            @else
                <article class="atlas-record-entry">
                    <div class="atlas-record-entry-head">
                        <h3 class="atlas-record-entry-title">
                            <a href="{{ route('admin.courses.assessment.edit', [$course, $assessment]) }}">{{ $assessment->title }}</a>
                        </h3>
                        <span class="atlas-stamp {{ $assessment->status === 'active' ? 'atlas-stamp--grn' : 'atlas-stamp--amb' }}">{{ ucfirst($assessment->status) }}</span>
                    </div>
                    <p class="atlas-record-entry-copy">{{ $assessment->description }}</p>
                    <dl class="log-facts atlas-record-entry-facts">
                        <div>
                            <dt>Passing score</dt>
                            <dd class="is-numeric">{{ $assessment->passing_score }}%</dd>
                        </div>
                        <div>
                            <dt>Version</dt>
                            <dd class="is-numeric">{{ $assessment->version }}</dd>
                        </div>
                    </dl>
                    <div class="atlas-record-entry-actions">
                        <a href="{{ route('admin.courses.assessment.edit', [$course, $assessment]) }}" class="btn btn-primary btn-sm">Edit Boss Challenge →</a>
                    </div>
                </article>
            @endif
        </section>
    </div>
@endsection
