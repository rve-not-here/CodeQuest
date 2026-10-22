@extends('layouts.app', ['role' => $role])

@section('title', $mission->title.' Knowledge Checks')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · {{ $course->name }}</span>
            <div class="atlas-records-running-links">
                <a href="{{ route('admin.courses.missions', $course) }}">← Challenges</a>
                <a href="{{ route('admin.courses.missions.edit', [$course, $mission]) }}">{{ $mission->title }} →</a>
            </div>
        </div>

        <h1 class="sr-only">Knowledge Checks</h1>

        @if (session('status'))
            <div class="atlas-records-alerts">
                <div class="panel border-success/40 bg-success-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-success">Saved</strong>
                    <span class="text-fg-muted">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <section class="atlas-record-group" aria-labelledby="checks-title">
            <div class="atlas-record-group-head">
                <h2 id="checks-title" class="atlas-record-group-title">Checks for {{ $mission->title }}</h2>
                <p class="atlas-record-group-figure">{{ $checks->count() }} {{ Str::plural('check', $checks->count()) }}</p>
            </div>

            @if ($checks->isEmpty())
                <section class="atlas-records-empty" aria-labelledby="empty-checks-title">
                    <h3 id="empty-checks-title" class="atlas-records-empty-title">No Knowledge Checks</h3>
                    <p class="atlas-records-empty-copy">No checks are defined for this challenge.</p>
                </section>
            @else
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Challenge Knowledge Checks</caption>
                        <thead>
                            <tr>
                                <th scope="col">Order</th>
                                <th scope="col">Check</th>
                                <th scope="col">Required</th>
                                <th scope="col">Status</th>
                                <th scope="col">Questions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($checks as $check)
                                <tr>
                                    <td data-label="Order"><span class="atlas-register-figure">{{ $check->order_num }}</span></td>

                                    <th scope="row" data-label="Check">
                                        <a href="{{ route('admin.courses.missions.checks.edit', [$course, $mission, $check]) }}">{{ $check->title }}</a>
                                    </th>

                                    <td data-label="Required">
                                        @if ($check->is_required)
                                            <span class="atlas-stamp atlas-stamp--acc">Required</span>
                                        @else
                                            <span class="atlas-stamp atlas-stamp--ink">Optional</span>
                                        @endif
                                    </td>

                                    <td data-label="Status">
                                        <span class="atlas-stamp {{ $check->status === 'published' ? 'atlas-stamp--grn' : 'atlas-stamp--ink' }}">{{ ucfirst($check->status) }}</span>
                                    </td>

                                    <td data-label="Questions"><span class="atlas-register-figure">{{ $check->questions_count }}</span></td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
