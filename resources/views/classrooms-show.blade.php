@extends('layouts.app', ['role' => $role])

@section('title', $classroom->name)

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · {{ $classroom->code ?? 'No code' }}</span>
            @if ($role === 'admin')
                <span class="atlas-records-running-links">
                    <a href="{{ route('classrooms') }}">All classrooms →</a>
                    <a href="{{ route('admin.classrooms.edit', $classroom) }}">Admin edit →</a>
                </span>
            @endif
        </div>

        <header class="atlas-records-heading">
            <h1 class="atlas-records-title">{{ $classroom->name }}</h1>
            <p class="atlas-records-lede">Students and course assignments in this classroom. Teacher visibility requires an active classroom.</p>
            <dl class="log-facts atlas-records-facts" aria-label="Classroom facts">
                <div><dt>Status</dt><dd>{{ ucfirst($classroom->status) }}</dd></div>
                <div><dt>Students</dt><dd class="is-numeric">{{ $classroom->students->count() }}</dd></div>
                <div><dt>Courses</dt><dd class="is-numeric">{{ $classroom->courses->count() }}</dd></div>
                <div><dt>Teachers</dt><dd class="is-numeric">{{ $classroom->teachers->count() }}</dd></div>
            </dl>
        </header>

        <section class="atlas-record-group" aria-labelledby="assigned-courses-title">
            <div class="atlas-record-group-head">
                <h2 id="assigned-courses-title" class="atlas-record-group-title">Assigned courses</h2>
                <p class="atlas-record-group-figure">{{ $classroom->courses->count() }} {{ Str::plural('course', $classroom->courses->count()) }}</p>
            </div>
            @if ($classroom->courses->isEmpty())
                <p class="atlas-record-group-note">No courses are assigned to this classroom.</p>
            @else
                <ul class="atlas-records-list">
                    @foreach ($classroom->courses as $course)
                        <li class="atlas-record-entry atlas-record-entry--compact">
                            <div class="atlas-record-entry-row atlas-record-entry-row--tag">
                                <h3 class="atlas-record-entry-title">{{ $course->name }}</h3>
                                <span class="atlas-stamp {{ $course->status === 'active' ? 'atlas-stamp--grn' : 'atlas-stamp--ink' }}">{{ ucfirst($course->status) }}</span>
                                <a href="{{ route('reports.teacher-course', $course) }}" class="atlas-row-action">Course report →</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="atlas-record-group" aria-labelledby="assigned-teachers-title">
            <div class="atlas-record-group-head">
                <h2 id="assigned-teachers-title" class="atlas-record-group-title">Assigned teachers</h2>
                <p class="atlas-record-group-figure">{{ $classroom->teachers->count() }} {{ Str::plural('teacher', $classroom->teachers->count()) }}</p>
            </div>
            @if ($classroom->teachers->isEmpty())
                <p class="atlas-record-group-note">No teachers are assigned to this classroom.</p>
            @else
                <ul class="atlas-records-list">
                    @foreach ($classroom->teachers as $teacher)
                        <li class="atlas-record-entry atlas-record-entry--compact">
                            <div class="atlas-record-entry-row atlas-record-entry-row--tag">
                                <h3 class="atlas-record-entry-title">{{ $teacher->username }}</h3>
                            </div>
                            @if ($teacher->name)
                                <p class="atlas-record-entry-copy">{{ $teacher->name }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="atlas-record-group" aria-labelledby="enrolled-students-title">
            <div class="atlas-record-group-head">
                <h2 id="enrolled-students-title" class="atlas-record-group-title">Enrolled students</h2>
                <p class="atlas-record-group-figure">{{ $classroom->students->count() }} {{ Str::plural('student', $classroom->students->count()) }}</p>
            </div>
            @if ($classroom->students->isEmpty())
                <p class="atlas-record-group-note">No students are enrolled in this classroom.</p>
            @else
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Students enrolled in {{ $classroom->name }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Student</th>
                                <th scope="col">Name</th>
                                <th scope="col">Report</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classroom->students as $student)
                                <tr>
                                    <th scope="row" data-label="Student"><a href="{{ route('student-progress', $student) }}">{{ $student->username }}</a></th>
                                    <td data-label="Name">{{ $student->name }}</td>
                                    <td data-label="Report"><a href="{{ route('reports.teacher-student', $student) }}" class="atlas-row-action">Student report →</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
