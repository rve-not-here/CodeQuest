@extends('layouts.app', ['role' => $role])

@section('title', $course->name.' sections')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Sections · {{ $course->name }}</span>
            <div class="atlas-records-running-links">
                <a href="{{ route('admin.courses.edit', $course) }}">← {{ $course->name }}</a>
                <a href="{{ route('admin.courses') }}">All courses →</a>
            </div>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Curriculum</span>
            <h1 class="atlas-records-title">Sections</h1>
            <p class="atlas-records-lede">{{ $course->name }} · Manage sections in learning order. Challenges reference a section of this course only.</p>
        </header>

        @if (session('status'))
            <div class="atlas-records-alerts">
                <div class="panel border-success/40 bg-success-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-success">Saved</strong>
                    <span class="text-fg-muted">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <section class="atlas-record-group" aria-labelledby="sections-title">
            <div class="atlas-record-group-head">
                <h2 id="sections-title" class="atlas-record-group-title">Sections in {{ $course->name }}</h2>
                <p class="atlas-record-group-figure">{{ $sections->count() }} {{ Str::plural('section', $sections->count()) }}</p>
            </div>

            @if ($sections->isEmpty())
                <section class="atlas-records-empty" aria-labelledby="empty-sections-title">
                    <h3 id="empty-sections-title" class="atlas-records-empty-title">No sections</h3>
                    <p class="atlas-records-empty-copy">No sections are defined for this course.</p>
                </section>
            @else
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Course sections</caption>
                        <thead>
                            <tr>
                                <th scope="col">Order</th>
                                <th scope="col">Section</th>
                                <th scope="col">Description</th>
                                <th scope="col">Challenges</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sections as $section)
                                <tr>
                                    <td data-label="Order"><span class="atlas-register-figure">{{ $section->order_num }}</span></td>

                                    <th scope="row" data-label="Section">
                                        <a href="{{ route('admin.courses.sections.edit', [$course, $section]) }}">{{ $section->title }}</a>
                                    </th>

                                    <td data-label="Description">{{ $section->description }}</td>

                                    <td data-label="Challenges"><span class="atlas-register-figure">{{ $section->missions_count }}</span></td>

                                    <td data-label="Actions">
                                        <div class="atlas-row-actions">
                                            <a href="{{ route('admin.courses.sections.edit', [$course, $section]) }}" class="atlas-row-action">Edit</a>
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
