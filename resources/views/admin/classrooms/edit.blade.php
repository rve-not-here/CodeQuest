@extends('layouts.app', ['role' => $role])

@section('title', 'Edit Classroom')

@section('content')
    <x-page-header
        title="Edit — {{ $classroom->name }}"
        subtitle="Base fields and the three membership sets. Each membership form REPLACES its set — uncheck everything to clear it. Memberships stay separate from academic history, and a status change never rewrites them."
    >
        <x-slot:actions>
            <x-badge tone="{{ $classroom->status === \App\Models\Classroom::STATUS_ACTIVE ? 'cyan' : 'dim' }}" class="uppercase">
                {{ $classroom->status }}
            </x-badge>
            <a href="{{ route('classrooms.show', $classroom) }}" class="btn-ghost">VIEW →</a>
            <a href="{{ route('admin.classrooms') }}" class="btn-ghost">◀ ALL CLASSROOMS</a>
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section>
            <h2 class="text-sm font-bold text-phosphor mb-3">BASE FIELDS</h2>

            <form method="POST" action="{{ route('admin.classrooms.update', $classroom) }}" class="panel p-4">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="block text-sm font-bold text-phosphor-dim mb-1">Name</label>
                    <input id="name" name="name" type="text" maxlength="128" value="{{ old('name', $classroom->name) }}" required class="terminal-input">
                    @error('name')
                        <span class="text-[15px] text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="code" class="block text-sm font-bold text-phosphor-dim mb-1">Code (optional)</label>
                    <input id="code" name="code" type="text" maxlength="32" value="{{ old('code', $classroom->code) }}" class="terminal-input">
                    @error('code')
                        <span class="text-[15px] text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="status" class="block text-sm font-bold text-phosphor-dim mb-1">Status</label>
                    <select id="status" name="status" class="terminal-input">
                        <option value="active" @selected(old('status', $classroom->status) === 'active')>ACTIVE</option>
                        <option value="inactive" @selected(old('status', $classroom->status) === 'inactive')>INACTIVE</option>
                    </select>
                    <p class="mt-2 text-[15px] text-phosphor-dim">
                        Deactivating removes teacher visibility immediately, without touching the memberships or any learning history.
                    </p>
                    @error('status')
                        <span class="text-[15px] text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn-ghost">SAVE BASE FIELDS →</button>
            </form>
        </section>

        <section class="space-y-6">
            <div>
                <h2 class="text-sm font-bold text-phosphor mb-3">ASSIGNED TEACHERS</h2>

                <form method="POST" action="{{ route('admin.classrooms.teachers', $classroom) }}" class="panel p-4">
                    @csrf

                    <div class="space-y-1 max-h-56 overflow-y-auto mb-4">
                        @forelse ($teacherOptions as $teacher)
                            <label class="flex items-center gap-2 text-lg">
                                <input
                                    type="checkbox"
                                    name="teacher_ids[]"
                                    value="{{ $teacher->id }}"
                                    @checked($classroom->teachers->contains('id', $teacher->id))
                                    class="terminal-checkbox"
                                >
                                <span>{{ $teacher->username }}</span>
                                <span class="text-[15px] text-phosphor-dim ml-2">{{ $teacher->name }}</span>
                            </label>
                        @empty
                            <x-status-message type="info" title="NO TEACHERS">No teacher accounts exist.</x-status-message>
                        @endforelse
                    </div>

                    <button type="submit" class="btn-ghost">REPLACE TEACHERS →</button>
                </form>
            </div>

            <div>
                <h2 class="text-sm font-bold text-phosphor mb-3">ENROLLED STUDENTS</h2>

                <form method="POST" action="{{ route('admin.classrooms.students', $classroom) }}" class="panel p-4">
                    @csrf

                    <div class="space-y-1 max-h-56 overflow-y-auto mb-4">
                        @forelse ($studentOptions as $student)
                            <label class="flex items-center gap-2 text-lg">
                                <input
                                    type="checkbox"
                                    name="student_ids[]"
                                    value="{{ $student->id }}"
                                    @checked($classroom->students->contains('id', $student->id))
                                    class="terminal-checkbox"
                                >
                                <span>{{ $student->username }}</span>
                                <span class="text-[15px] text-phosphor-dim ml-2">{{ $student->name }}</span>
                            </label>
                        @empty
                            <x-status-message type="info" title="NO STUDENTS">No student accounts exist.</x-status-message>
                        @endforelse
                    </div>

                    <button type="submit" class="btn-ghost">REPLACE STUDENTS →</button>
                </form>
            </div>

            <div>
                <h2 class="text-sm font-bold text-phosphor mb-3">ASSIGNED COURSES</h2>

                <form method="POST" action="{{ route('admin.classrooms.courses', $classroom) }}" class="panel p-4">
                    @csrf

                    <div class="space-y-1 max-h-56 overflow-y-auto mb-4">
                        @forelse ($courseOptions as $course)
                            <label class="flex items-center gap-2 text-lg">
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
                    </div>

                    <button type="submit" class="btn-ghost">REPLACE COURSES →</button>
                </form>
            </div>
        </section>
    </div>
@endsection