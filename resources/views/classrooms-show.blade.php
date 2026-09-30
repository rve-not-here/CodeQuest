@extends('layouts.app', ['role' => $role])

@section('title', $classroom->name)

@section('content')
    <x-page-header
        title="{{ $classroom->name }}"
        subtitle="The enrollment container behind your monitoring scope. The students enrolled and courses assigned here are exactly what the teacher-area pages can surface while this classroom is ACTIVE."
        icon="▣"
    >
        <x-slot:actions>
            <x-badge tone="{{ $classroom->status === \App\Models\Classroom::STATUS_ACTIVE ? 'cyan' : 'dim' }}" class="uppercase">
                {{ $classroom->status }}
            </x-badge>
            <a href="{{ route('classrooms') }}" class="btn-ghost">◀ ALL CLASSROOMS</a>
            @if ($role === 'admin')
                <a href="{{ route('admin.classrooms.edit', $classroom) }}" class="btn-ghost">ADMIN EDIT →</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <section class="panel p-4">
            <h2 class="text-sm font-bold text-phosphor mb-3">ASSIGNED TEACHERS</h2>

            @if ($classroom->teachers->isEmpty())
                <x-status-message type="info" title="NO TEACHERS">No teachers are assigned to this classroom.</x-status-message>
            @else
                <ul class="font-body text-lg space-y-1">
                    @foreach ($classroom->teachers as $teacher)
                        <li class="flex items-center justify-between border-b border-phosphor-dim/40 py-2">
                            <span class="text-phosphor">{{ $teacher->username }}</span>
                            <span class="text-[15px] text-phosphor-dim">{{ $teacher->name }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="panel p-4">
            <h2 class="text-sm font-bold text-phosphor mb-3">ENROLLED STUDENTS</h2>

            @if ($classroom->students->isEmpty())
                <x-status-message type="info" title="NO STUDENTS">No students are enrolled in this classroom.</x-status-message>
            @else
                <ul class="font-body text-lg space-y-1">
                    @foreach ($classroom->students as $student)
                        <li class="flex items-center justify-between border-b border-phosphor-dim/40 py-2">
                            <a href="{{ route('student-progress', $student) }}" class="text-cyan hover:underline">
                                {{ $student->username }} →
                            </a>
                            <span class="text-[15px] text-phosphor-dim">{{ $student->name }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="panel p-4">
            <h2 class="text-sm font-bold text-phosphor mb-3">ASSIGNED COURSES</h2>

            @if ($classroom->courses->isEmpty())
                <x-status-message type="info" title="NO COURSES">No courses are assigned to this classroom.</x-status-message>
            @else
                <ul class="font-body text-lg space-y-1">
                    @foreach ($classroom->courses as $course)
                        <li class="flex items-center justify-between border-b border-phosphor-dim/40 py-2">
                            <span class="text-phosphor">{{ $course->name }}</span>
                            <x-badge tone="{{ $course->status === 'active' ? 'cyan' : 'dim' }}" class="uppercase">{{ $course->status }}</x-badge>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection