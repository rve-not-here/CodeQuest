@extends('layouts.app', ['role' => auth()->user()->role])

@section('title', 'Reports')

@section('content')
    @if (auth()->user()->role === 'teacher')
        <div class="teacher-operations">
            <header class="teacher-context"><div><h1>Reports</h1><p>Choose a student or course from your current classroom scope.</p></div><a href="{{ route('classrooms') }}" class="btn-ghost">My classrooms →</a></header>
            <section class="teacher-report-destination" aria-labelledby="student-reports">
                <div><h2 id="student-reports">Student reports</h2><p>Open a student from the roster, then choose View Student Report on their progress page.</p></div>
                <a href="{{ route('students') }}" class="btn-primary">CHOOSE STUDENT →</a>
            </section>
            <section class="teacher-report-destination" aria-labelledby="course-reports">
                <div><h2 id="course-reports">Course reports</h2><p>Open a course report from Course Analytics to inspect lifecycle, competency and period evidence.</p></div>
                <a href="{{ route('course-analytics') }}" class="btn-ghost">CHOOSE COURSE →</a>
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
