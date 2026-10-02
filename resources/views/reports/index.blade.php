@extends('layouts.app', ['role' => auth()->user()->role])

@section('title', 'Reports')

@section('content')
    <x-page-header title="Reports" subtitle="Choose a student or course from your current classroom scope." icon="Σ" />
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
@endsection
