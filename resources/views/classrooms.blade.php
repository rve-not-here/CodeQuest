@extends('layouts.app', ['role' => $role])

@section('title', 'My Classrooms')

@section('content')
    <div class="teacher-operations">
        <header class="teacher-context">
            <div>
                <h1>My Classrooms</h1>
                <p>{{ $role === 'teacher' ? 'Your active teaching classrooms define the students and courses you can monitor.' : 'All classrooms, including inactive assignments.' }}</p>
            </div>
            @if ($role === 'admin')
                <a href="{{ route('admin.classrooms') }}" class="btn-ghost">ADMIN MANAGEMENT →</a>
            @else
                <a href="{{ route('students') }}" class="btn-ghost">Student roster →</a>
            @endif
        </header>

        @if ($classrooms->isEmpty())
            <section class="teacher-empty" aria-labelledby="classrooms-empty">
                <h2 id="classrooms-empty">NO CLASSROOMS</h2>
                <p>{{ $role === 'admin' ? 'No classrooms exist yet. Create one from the admin management surface.' : 'You are not assigned to any active classroom. An admin must assign you before monitoring is available.' }}</p>
            </section>
        @else
            <section class="teacher-record-section" aria-labelledby="classrooms-title">
                <div class="teacher-section-heading">
                    <h2 id="classrooms-title">Teaching assignments</h2>
                    <p class="teacher-state">{{ $classrooms->count() }} {{ Str::plural('classroom', $classrooms->count()) }}</p>
                </div>
                <table class="table-stack teacher-evidence-table" role="table">
                    <caption>Classroom membership and course assignments</caption>
                    <thead>
                        <tr><th scope="col">Classroom</th><th scope="col">Code</th><th scope="col">Status</th><th scope="col">Students</th><th scope="col">Courses</th><th scope="col">Open</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($classrooms as $classroom)
                            <tr>
                                <th scope="row" data-label="Classroom"><a href="{{ route('classrooms.show', $classroom) }}">{{ $classroom->name }}</a></th>
                                <td data-label="Code"><span class="teacher-state">{{ $classroom->code ?? '—' }}</span></td>
                                <td data-label="Status"><span class="teacher-state">{{ strtoupper($classroom->status) }}</span></td>
                                <td data-label="Students">{{ $classroom->students_count }}</td>
                                <td data-label="Courses">{{ $classroom->courses_count }}</td>
                                <td data-label="Open"><a href="{{ route('classrooms.show', $classroom) }}" aria-label="View {{ $classroom->name }}">VIEW →</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="teacher-note">An inactive classroom removes teacher visibility without changing membership or learning history.</p>
            </section>
        @endif
    </div>
@endsection
