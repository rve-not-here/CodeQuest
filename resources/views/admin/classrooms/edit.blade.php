@extends('layouts.app', ['role' => $role])

@section('title', 'Edit Classroom')

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>Edit — {{ $classroom->name }}</h1><p>Manage classroom details and memberships. Save each membership group separately.</p></div>
        <div class="admin-actions"><x-badge tone="{{ $classroom->status === \App\Models\Classroom::STATUS_ACTIVE ? 'cyan' : 'dim' }}" class="uppercase">
                {{ $classroom->status }}
            </x-badge>
            <a href="{{ route('classrooms.show', $classroom) }}" class="btn-ghost">VIEW →</a>
            <a href="{{ route('admin.classrooms') }}" class="btn-ghost">◀ ALL CLASSROOMS</a></div>
    </header>

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
        <section>
            <h2 class="text-sm font-bold text-accent mb-3">BASE FIELDS</h2>

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
                    <p id="status-help" class="mt-2 text-[15px] text-fg-muted">
                        Deactivating removes teacher visibility immediately, without touching the memberships or any learning history.
                    </p>
                </div>

                <button type="submit" class="btn-ghost">SAVE BASE FIELDS →</button>
            </form>
        </section>

        <section class="admin-membership-groups">
            <div>
                <h2 class="text-sm font-bold text-accent mb-3">ASSIGNED TEACHERS</h2>

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

                    <button type="submit" class="btn-ghost">REPLACE TEACHERS →</button>
                </form>
            </div>

            <div>
                <h2 class="text-sm font-bold text-accent mb-3">ENROLLED STUDENTS</h2>

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

                    <button type="submit" class="btn-ghost">REPLACE STUDENTS →</button>
                </form>
            </div>

            <div>
                <h2 class="text-sm font-bold text-accent mb-3">ASSIGNED COURSES</h2>

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

                    <button type="submit" class="btn-ghost">REPLACE COURSES →</button>
                </form>
            </div>
        </section>
    </div>
    </div>
@endsection
