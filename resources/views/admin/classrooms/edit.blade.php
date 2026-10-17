@extends('layouts.app', ['role' => $role])

@section('title', 'Edit Classroom')

@section('content')
    <div class="admin-management">
    <nav class="admin-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('admin.classrooms') }}">Classrooms</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ $classroom->name }}</span>
    </nav>

    <x-page-header title="Edit — {{ $classroom->name }}" subtitle="Manage classroom details and memberships. Save each membership group separately.">
        <x-slot:actions>
            <x-badge tone="{{ $classroom->status === \App\Models\Classroom::STATUS_ACTIVE ? 'cyan' : 'dim' }}" class="uppercase">
                {{ $classroom->status }}
            </x-badge>
            <a href="{{ route('classrooms.show', $classroom) }}" class="btn-ghost">VIEW →</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    @if (session('error'))
        <x-status-message type="error" title="WRITE REFUSED" class="mb-4">
            {{ session('error') }}
        </x-status-message>
    @endif

    @if ($errors->any())
        <x-status-message type="error" title="INPUT REJECTED" class="mb-4">
            <ul>
                @foreach ($errors->messages() as $field => $messages)
                    @foreach ($messages as $error)
                        <li><a href="#{{ Str::before($field, '.') }}">{{ $error }}</a></li>
                    @endforeach
                @endforeach
            </ul>
        </x-status-message>
    @endif

    <div class="admin-membership-layout">
        <x-panel title="Base Fields">
            <form method="POST" action="{{ route('admin.classrooms.update', $classroom) }}" class="admin-form">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="block text-sm font-bold text-fg-muted mb-1">Name</label>
                    <input id="name" name="name" type="text" maxlength="128" value="{{ old('name', $classroom->name) }}" required class="terminal-input" @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                    @error('name')<p id="name-error" class="admin-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="mb-4">
                    <label for="code" class="block text-sm font-bold text-fg-muted mb-1">Code (optional)</label>
                    <input id="code" name="code" type="text" maxlength="32" value="{{ old('code', $classroom->code) }}" class="terminal-input" @if ($errors->has('code')) aria-invalid="true" aria-describedby="code-error" @endif>
                    @error('code')<p id="code-error" class="admin-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="mb-6">
                    <label for="status" class="block text-sm font-bold text-fg-muted mb-1">Status</label>
                    <select id="status" name="status" class="terminal-input" aria-describedby="{{ $errors->has('status') ? 'status-help status-error' : 'status-help' }}" @if ($errors->has('status')) aria-invalid="true" @endif>
                        <option value="active" @selected(old('status', $classroom->status) === 'active')>ACTIVE</option>
                        <option value="inactive" @selected(old('status', $classroom->status) === 'inactive')>INACTIVE</option>
                    </select>
                    @error('status')<p id="status-error" class="admin-field-error">{{ $message }}</p>@enderror
                    <p id="status-help" class="admin-field-help">
                        Deactivating removes teacher visibility immediately, without touching the memberships or any learning history.
                    </p>
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="btn-primary">SAVE BASE FIELDS →</button>
                </div>
            </form>
        </x-panel>

        <div class="admin-ledger">
            <x-panel title="Assigned Teachers">
                <form method="POST" action="{{ route('admin.classrooms.teachers', $classroom) }}" class="admin-form">
                    @csrf

                    <fieldset id="teacher_ids" class="admin-membership-options"><legend>Assigned teachers</legend>
                        @forelse ($teacherOptions as $teacher)
                            <label class="admin-membership-option">
                                <input
                                    type="checkbox"
                                    name="teacher_ids[]"
                                    value="{{ $teacher->id }}"
                                    @checked($classroom->teachers->contains('id', $teacher->id))
                                    class="terminal-checkbox"
                                >
                                <span>{{ $teacher->username }}</span>
                                <span class="text-[15px] text-fg-muted ml-2">{{ $teacher->name }}</span>
                            </label>
                        @empty
                            <x-status-message type="info" title="NO TEACHERS">No teacher accounts exist.</x-status-message>
                        @endforelse
                    </fieldset>

                    <div class="admin-form-actions">
                        <button type="submit" class="btn-ghost">REPLACE TEACHERS →</button>
                    </div>
                </form>
            </x-panel>

            <x-panel title="Enrolled Students">
                <form method="POST" action="{{ route('admin.classrooms.students', $classroom) }}" class="admin-form">
                    @csrf

                    <fieldset id="student_ids" class="admin-membership-options"><legend>Enrolled students</legend>
                        @forelse ($studentOptions as $student)
                            <label class="admin-membership-option">
                                <input
                                    type="checkbox"
                                    name="student_ids[]"
                                    value="{{ $student->id }}"
                                    @checked($classroom->students->contains('id', $student->id))
                                    class="terminal-checkbox"
                                >
                                <span>{{ $student->username }}</span>
                                <span class="text-[15px] text-fg-muted ml-2">{{ $student->name }}</span>
                            </label>
                        @empty
                            <x-status-message type="info" title="NO STUDENTS">No student accounts exist.</x-status-message>
                        @endforelse
                    </fieldset>

                    <div class="admin-form-actions">
                        <button type="submit" class="btn-ghost">REPLACE STUDENTS →</button>
                    </div>
                </form>
            </x-panel>

            <x-panel title="Assigned Courses">
                <form method="POST" action="{{ route('admin.classrooms.courses', $classroom) }}" class="admin-form">
                    @csrf

                    <fieldset id="course_ids" class="admin-membership-options"><legend>Assigned courses</legend>
                        @forelse ($courseOptions as $course)
                            <label class="admin-membership-option">
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
                            <x-status-message type="info" title="NO COURSES">No courses exist.</x-status-message>
                        @endforelse
                    </fieldset>

                    <div class="admin-form-actions">
                        <button type="submit" class="btn-ghost">REPLACE COURSES →</button>
                    </div>
                </form>
            </x-panel>
        </div>
    </div>
    </div>
@endsection
