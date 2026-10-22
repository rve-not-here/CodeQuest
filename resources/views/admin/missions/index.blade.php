@extends('layouts.app', ['role' => $role])

@section('title', $course->name.' challenges')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · {{ $course->name }}</span>
            <a href="{{ route('admin.courses') }}">All courses →</a>
        </div>

    @include('admin.courses._nav', ['course' => $course, 'active' => 'missions'])

        <h1 class="sr-only">Challenges</h1>

        @if (session('status'))
            <div class="atlas-records-alerts">
                <div class="panel border-success/40 bg-success-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-success">Saved</strong>
                    <span class="text-fg-muted">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <section class="atlas-record-group" aria-labelledby="challenges-title">
            <div class="atlas-record-group-head">
                <h2 id="challenges-title" class="atlas-record-group-title">Challenge register</h2>
                <p class="atlas-record-group-figure">{{ $missions->count() }} {{ Str::plural('challenge', $missions->count()) }}</p>
            </div>

            @if ($missions->isEmpty())
                <section class="atlas-records-empty" aria-labelledby="empty-challenges-title">
                    <h3 id="empty-challenges-title" class="atlas-records-empty-title">No challenges</h3>
                    <p class="atlas-records-empty-copy">No challenges are defined for this course.</p>
                </section>
            @else
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Course challenges</caption>
                        <thead>
                            <tr>
                                <th scope="col">Order</th>
                                <th scope="col">Challenge</th>
                                <th scope="col">Difficulty</th>
                                <th scope="col" class="md:text-right">Points</th>
                                <th scope="col">Section</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($missions as $mission)
                                @php
                                    $difficultyTone = match ($mission->difficulty) {
                                        'HARD' => 'atlas-stamp--amb',
                                        'MEDIUM' => 'atlas-stamp--vio',
                                        default => 'atlas-stamp--ink',
                                    };
                                @endphp

                                <tr>
                                    <td data-label="Order"><span class="atlas-register-figure">{{ $mission->order_num }}</span></td>

                                    <th scope="row" data-label="Challenge">
                                        <a href="{{ route('admin.courses.missions.edit', [$course, $mission]) }}">{{ $mission->title }}</a>
                                    </th>

                                    <td data-label="Difficulty">
                                        <span class="atlas-stamp {{ $difficultyTone }}">{{ ucfirst(strtolower($mission->difficulty)) }}</span>
                                    </td>

                                    <td data-label="Points"><span class="atlas-register-figure">+{{ $mission->points }} XP</span></td>

                                    <td data-label="Section">{{ $mission->section?->title ?? '—' }}</td>

                                    <td data-label="Actions">
                                        <div class="atlas-row-actions">
                                            <a href="{{ route('admin.courses.missions.checks', [$course, $mission]) }}" class="atlas-row-action">Checks</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
