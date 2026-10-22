@extends('layouts.app', ['role' => $role])

@section('title', 'My Classrooms')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · {{ $role === 'admin' ? 'Platform scope' : 'Classroom scope' }}</span>
            @if ($role === 'admin')
                <span class="atlas-records-running-links">
                    <a href="{{ route('admin.classrooms') }}">Admin management →</a>
                    <a href="{{ route('students') }}">Students →</a>
                </span>
            @endif
        </div>

        <header class="atlas-records-heading">
            <h1 class="atlas-records-title">My classrooms</h1>
            <p class="atlas-records-lede">{{ $role === 'teacher' ? 'Your active teaching classrooms define the students and courses you can monitor.' : 'All classrooms, including inactive assignments.' }}</p>
            <dl class="log-facts atlas-records-facts" aria-label="Classroom scope">
                <div><dt>Classrooms</dt><dd class="is-numeric">{{ $classrooms->count() }}</dd></div>
                <div><dt>Active</dt><dd class="is-numeric">{{ $classrooms->where('status', 'active')->count() }}</dd></div>
                <div><dt>Student enrolments</dt><dd class="is-numeric">{{ $classrooms->sum('students_count') }}</dd></div>
                <div><dt>Course assignments</dt><dd class="is-numeric">{{ $classrooms->sum('courses_count') }}</dd></div>
            </dl>
        </header>

        @if ($classrooms->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="empty-classrooms-title">
                <h2 id="empty-classrooms-title" class="atlas-records-empty-title">No classrooms</h2>
                <p class="atlas-records-empty-copy">{{ $role === 'admin' ? 'No classrooms exist yet. Create one from the admin management surface.' : 'You are not assigned to any active classroom. An admin must assign you before monitoring is available.' }}</p>
                @if ($role === 'admin')
                    <a href="{{ route('admin.classrooms') }}" class="btn btn-ghost">Admin management →</a>
                @endif
            </section>
        @else
            <section class="atlas-record-group" aria-labelledby="classrooms-title">
                <div class="atlas-record-group-head">
                    <h2 id="classrooms-title" class="atlas-record-group-title">Teaching assignments</h2>
                    <p class="atlas-record-group-figure">{{ $classrooms->count() }} {{ Str::plural('classroom', $classrooms->count()) }}</p>
                </div>
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Classroom membership and course assignments</caption>
                        <thead>
                            <tr>
                                <th scope="col">Classroom</th>
                                <th scope="col">Code</th>
                                <th scope="col">Status</th>
                                <th scope="col">Students</th>
                                <th scope="col">Courses</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classrooms as $classroom)
                                <tr>
                                    <th scope="row" data-label="Classroom">
                                        <a href="{{ route('classrooms.show', $classroom) }}">{{ $classroom->name }}</a>
                                    </th>
                                    <td data-label="Code"><span class="atlas-register-figure">{{ $classroom->code ?? '—' }}</span></td>
                                    <td data-label="Status">
                                        @if ($classroom->status === 'active')
                                            <span class="atlas-stamp atlas-stamp--grn">Active</span>
                                        @else
                                            <span class="atlas-stamp atlas-stamp--ink">{{ ucfirst($classroom->status) }}</span>
                                        @endif
                                    </td>
                                    <td data-label="Students"><span class="atlas-register-figure">{{ $classroom->students_count }}</span></td>
                                    <td data-label="Courses"><span class="atlas-register-figure">{{ $classroom->courses_count }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="atlas-record-group-note">An inactive classroom removes teacher visibility without changing membership or learning history.</p>
            </section>
        @endif
    </div>
@endsection
