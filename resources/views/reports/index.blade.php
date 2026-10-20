@extends('layouts.app', ['role' => auth()->user()->role])

@section('title', 'Reports')

@section('content')
    @php
        $isTeacher = auth()->user()->role === 'teacher';
    @endphp

    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Reports · {{ $isTeacher ? 'Classroom scope' : 'Platform scope' }}</span>
            @if ($isTeacher)
                <a href="{{ route('classrooms') }}">My classrooms →</a>
            @else
                <a href="{{ route('admin.system') }}">System status →</a>
            @endif
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Reports</span>
            <h1 class="atlas-records-title">Reports</h1>
            <p class="atlas-records-lede">
                @if ($isTeacher)
                    Choose a student or course from your current classroom scope. Reports are generated from recorded evidence only.
                @else
                    Choose a student or course across the platform. Reports are generated from recorded evidence only.
                @endif
            </p>
        </header>

        <section class="atlas-record-group" aria-labelledby="student-reports">
            <div class="atlas-record-group-head">
                <div>
                    <span class="atlas-record-entry-label">Register 01</span>
                    <h2 id="student-reports" class="atlas-record-group-title">Student reports</h2>
                </div>
            </div>
            <article class="atlas-record-entry">
                <h3 class="atlas-record-entry-title">Per-student progress evidence</h3>
                <p class="atlas-record-entry-copy">Open a student from the roster, then choose Student report on their progress page. Every table names its own time window.</p>
                <div class="atlas-record-entry-actions">
                    <a href="{{ route('students') }}" class="btn btn-primary">Choose student →</a>
                </div>
            </article>
        </section>

        <section class="atlas-record-group" aria-labelledby="course-reports">
            <div class="atlas-record-group-head">
                <div>
                    <span class="atlas-record-entry-label">Register 02</span>
                    <h2 id="course-reports" class="atlas-record-group-title">Course reports</h2>
                </div>
            </div>
            <article class="atlas-record-entry">
                <h3 class="atlas-record-entry-title">Per-course lifecycle evidence</h3>
                <p class="atlas-record-entry-copy">Open a course report from Course analytics to inspect lifecycle, competency and period evidence.</p>
                <div class="atlas-record-entry-actions">
                    <a href="{{ route('course-analytics') }}" class="btn btn-secondary">Choose course →</a>
                </div>
            </article>
        </section>
    </div>
@endsection
