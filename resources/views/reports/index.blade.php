@extends('layouts.app', ['role' => auth()->user()->role])

@section('title', 'Reports')

@section('content')
    @if (auth()->user()->role === 'teacher')
        <div class="atlas-records">
            <div class="log-running atlas-records-running">
                <span>Records · Reports · Classroom scope</span>
                <a href="{{ route('classrooms') }}">My classrooms →</a>
            </div>

            <header class="atlas-records-heading">
                <span class="atlas-lesson-entry-label">Records · Reports</span>
                <h1 class="atlas-records-title">Reports</h1>
                <p class="atlas-records-lede">Choose a student or course from your current classroom scope. Reports are generated from recorded evidence only.</p>
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
                        <a href="{{ route('course-analytics') }}" class="btn-ghost">Choose course →</a>
                    </div>
                </article>
            </section>
        </div>
    @else
    <x-page-header title="Reports" subtitle="Choose a student or course from your current classroom scope." icon="table-2" />
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <section class="panel p-5">
            <h2 class="text-lg font-semibold">Student reports</h2>
            <p class="mt-2 text-sm text-fg-muted">Open a student from the roster, then choose View Student Report on their progress page.</p>
            <a href="{{ route('students') }}" class="btn btn-primary mt-4">CHOOSE STUDENT →</a>
        </section>
        <section class="panel p-5">
            <h2 class="text-lg font-semibold">Course reports</h2>
            <p class="mt-2 text-sm text-fg-muted">Open a course report from Course Analytics to inspect lifecycle, competency and period evidence.</p>
            <a href="{{ route('course-analytics') }}" class="btn btn-primary mt-4">CHOOSE COURSE →</a>
        </section>
    </div>
    @endif
@endsection
