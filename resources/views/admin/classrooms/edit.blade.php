@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$classroom->name)

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Platform scope</span>
            <div class="atlas-records-running-links">
                <a href="{{ route('classrooms.show', $classroom) }}">Classroom sheet →</a>
            </div>
        </div>

        <h1 class="sr-only">{{ $classroom->name }}</h1>

        @if (session('status'))
            <div class="atlas-records-alerts">
                <div class="panel border-success/40 bg-success-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-success">Saved</strong>
                    <span class="text-fg-muted">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="atlas-records-alerts">
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Write refused</strong>
                    <span class="text-fg-muted">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="atlas-records-alerts">
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Input rejected</strong>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->messages() as $field => $messages)
                            @foreach ($messages as $error)
                                <li><a href="#{{ Str::before($field, '.') }}" class="underline underline-offset-2">{{ $error }}</a></li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section class="atlas-record-group" aria-labelledby="base-fields-title">
            <div class="atlas-record-group-head">
                <h2 id="base-fields-title" class="atlas-record-group-title">Base fields</h2>
                <p class="atlas-record-group-figure">Name, code and visibility</p>
            </div>

            <form method="POST" action="{{ route('admin.classrooms.update', $classroom) }}" class="atlas-form">
                @csrf
                @method('PUT')

                <div class="atlas-form-grid">
                    <div class="atlas-form-field">
                        <label for="name" class="atlas-lesson-entry-label">Name</label>
                        <input id="name" name="name" type="text" maxlength="128" value="{{ old('name', $classroom->name) }}" required class="terminal-input" @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                        @error('name')<p id="name-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="code" class="atlas-lesson-entry-label">Code (optional)</label>
                        <input id="code" name="code" type="text" maxlength="32" value="{{ old('code', $classroom->code) }}" class="terminal-input" @if ($errors->has('code')) aria-invalid="true" aria-describedby="code-error" @endif>
                        @error('code')<p id="code-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="status" class="atlas-lesson-entry-label">Status</label>
                        <select id="status" name="status" class="terminal-input" aria-describedby="{{ $errors->has('status') ? 'status-help status-error' : 'status-help' }}" @if ($errors->has('status')) aria-invalid="true" @endif>
                            <option value="active" @selected(old('status', $classroom->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $classroom->status) === 'inactive')>Inactive</option>
                        </select>
                        @error('status')<p id="status-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        <p id="status-help" class="atlas-field-help">Deactivating removes teacher visibility immediately, without touching memberships or any learning history.</p>
                    </div>
                </div>

                <div class="atlas-form-actions">
                    <button type="submit" class="btn btn-primary">Save base fields →</button>
                </div>
            </form>
        </section>

        <section class="atlas-record-group" aria-labelledby="teachers-title">
            <div class="atlas-record-group-head">
                <h2 id="teachers-title" class="atlas-record-group-title">Assigned teachers</h2>
                <p class="atlas-record-group-figure">{{ $classroom->teachers->count() }} of {{ $teacherOptions->count() }}</p>
            </div>

            <form method="POST" action="{{ route('admin.classrooms.teachers', $classroom) }}">
                @csrf

                <fieldset id="teacher_ids" class="atlas-choice-list">
                    <legend class="sr-only">Assigned teachers</legend>
                    @forelse ($teacherOptions as $teacher)
                        <label class="atlas-choice">
                            <input
                                type="checkbox"
                                name="teacher_ids[]"
                                value="{{ $teacher->id }}"
                                @checked($classroom->teachers->contains('id', $teacher->id))
                                class="terminal-checkbox"
                            >
                            <span>{{ $teacher->username }}</span>
                            <span class="atlas-choice-name">{{ $teacher->name }}</span>
                        </label>
                    @empty
                        <p class="atlas-record-group-note">No teacher accounts exist.</p>
                    @endforelse
                </fieldset>

                <div class="atlas-form">
                    <div class="atlas-form-actions">
                        <button type="submit" class="btn btn-secondary">Replace teachers →</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="atlas-record-group" aria-labelledby="students-title">
            <div class="atlas-record-group-head">
                <h2 id="students-title" class="atlas-record-group-title">Enrolled students</h2>
                <p class="atlas-record-group-figure">{{ $classroom->students->count() }} of {{ $studentOptions->count() }}</p>
            </div>

            <form method="POST" action="{{ route('admin.classrooms.students', $classroom) }}">
                @csrf

                <fieldset id="student_ids" class="atlas-choice-list">
                    <legend class="sr-only">Enrolled students</legend>
                    @forelse ($studentOptions as $student)
                        <label class="atlas-choice">
                            <input
                                type="checkbox"
                                name="student_ids[]"
                                value="{{ $student->id }}"
                                @checked($classroom->students->contains('id', $student->id))
                                class="terminal-checkbox"
                            >
                            <span>{{ $student->username }}</span>
                            <span class="atlas-choice-name">{{ $student->name }}</span>
                        </label>
                    @empty
                        <p class="atlas-record-group-note">No student accounts exist.</p>
                    @endforelse
                </fieldset>

                <div class="atlas-form">
                    <div class="atlas-form-actions">
                        <button type="submit" class="btn btn-secondary">Replace students →</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="atlas-record-group" aria-labelledby="courses-title">
            <div class="atlas-record-group-head">
                <h2 id="courses-title" class="atlas-record-group-title">Assigned courses</h2>
                <p class="atlas-record-group-figure">{{ $classroom->courses->count() }} of {{ $courseOptions->count() }}</p>
            </div>

            <form method="POST" action="{{ route('admin.classrooms.courses', $classroom) }}">
                @csrf

                <fieldset id="course_ids" class="atlas-choice-list">
                    <legend class="sr-only">Assigned courses</legend>
                    @forelse ($courseOptions as $course)
                        <label class="atlas-choice">
                            <input
                                type="checkbox"
                                name="course_ids[]"
                                value="{{ $course->id }}"
                                @checked($classroom->courses->contains('id', $course->id))
                                class="terminal-checkbox"
                            >
                            <span>{{ $course->name }}</span>
                        </label>
                    @empty
                        <p class="atlas-record-group-note">No courses exist.</p>
                    @endforelse
                </fieldset>

                <div class="atlas-form">
                    <div class="atlas-form-actions">
                        <button type="submit" class="btn btn-secondary">Replace courses →</button>
                    </div>
                </div>
            </form>
        </section>
    </div>
@endsection
