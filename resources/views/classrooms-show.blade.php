@extends('layouts.app', ['role' => $role])

@section('title', $classroom->name)

@section('content')
    <div class="teacher-operations">
        <header class="teacher-context">
            <div>
                <h1>{{ $classroom->name }}</h1>
                <p><span class="teacher-state">{{ $classroom->code ?? '—' }} · {{ strtoupper($classroom->status) }}</span></p>
                <p>Students and course assignments in this classroom. Teacher visibility requires an active classroom.</p>
            </div>
            <div class="teacher-context-actions">
                <a href="{{ route('classrooms') }}" class="btn-ghost">◀ ALL CLASSROOMS</a>
                @if ($role === 'admin')
                    <a href="{{ route('admin.classrooms.edit', $classroom) }}" class="btn-ghost">ADMIN EDIT →</a>
                @endif
            </div>
        </header>
        <div class="teacher-classroom-layout">
            <div>
                <section class="teacher-record-section" aria-labelledby="assigned-courses">
                    <h2 id="assigned-courses">ASSIGNED COURSES</h2>
                    <ul class="teacher-record-list">
                        @forelse ($classroom->courses as $course)
                            <li><strong>{{ $course->name }}</strong><span class="teacher-state">{{ strtoupper($course->status) }}</span></li>
                        @empty
                            <li class="teacher-note">NO COURSES · No courses are assigned to this classroom.</li>
                        @endforelse
                    </ul>
                </section>
                <section class="teacher-record-section" aria-labelledby="assigned-teachers">
                    <h2 id="assigned-teachers">ASSIGNED TEACHERS</h2>
                    <ul class="teacher-record-list">
                        @forelse ($classroom->teachers as $teacher)
                            <li><strong>{{ $teacher->username }}</strong><span>{{ $teacher->name }}</span></li>
                        @empty
                            <li class="teacher-note">NO TEACHERS · No teachers are assigned to this classroom.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
            <section class="teacher-record-section" aria-labelledby="enrolled-students">
                <div class="teacher-section-heading"><h2 id="enrolled-students">ENROLLED STUDENTS</h2><p class="teacher-state">{{ $classroom->students->count() }} students</p></div>
                @if ($classroom->students->isEmpty())
                    <p class="teacher-note">NO STUDENTS · No students are enrolled in this classroom.</p>
                @else
                    <table class="table-stack teacher-evidence-table" role="table">
                        <caption>Students enrolled in {{ $classroom->name }}</caption>
                        <thead><tr><th scope="col">Student</th><th scope="col">Name</th></tr></thead>
                        <tbody>
                            @foreach ($classroom->students as $student)
                                <tr><th scope="row" data-label="Student"><a href="{{ route('student-progress', $student) }}">{{ $student->username }} →</a></th><td data-label="Name">{{ $student->name }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        </div>
    </div>
@endsection
