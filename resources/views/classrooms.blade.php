@extends('layouts.app', ['role' => $role])

@section('title', 'My Classrooms')

@section('content')
    <x-page-header
        title="My Classrooms"
        subtitle="The classrooms that authorize your monitoring scope. Teachers see only their own teaching classrooms while ACTIVE — an inactive classroom removes visibility immediately without touching its memberships or any learning history. Admins see every classroom."
    >
        <x-slot:actions>
            @if ($role === 'admin')
                <a href="{{ route('admin.classrooms') }}" class="btn-ghost">ADMIN MANAGEMENT →</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($classrooms->isEmpty())
        <x-status-message type="info" title="NO CLASSROOMS">
            @if ($role === 'admin')
                No classrooms exist yet. Create one from the admin management surface.
            @else
                You are not assigned to any active classroom. Your monitoring scope opens as soon as an admin assigns you to an active classroom.
            @endif
        </x-status-message>
    @else
        <div class="panel p-0 overflow-x-auto">
            <table class="table-stack w-full text-left">
                <thead>
                    <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                        <th class="px-4 py-3">Classroom</th>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Students</th>
                        <th class="px-4 py-3">Courses</th>
                        <th class="px-4 py-3">Open</th>
                    </tr>
                </thead>
                <tbody class="font-body text-lg">
                    @foreach ($classrooms as $classroom)
                        <tr class="border-b border-phosphor-dim/40 align-top">
                            <td class="px-4 py-3" data-label="Classroom">
                                <a href="{{ route('classrooms.show', $classroom) }}" class="text-phosphor hover:underline">
                                    {{ $classroom->name }}
                                </a>
                            </td>

                            <td class="px-4 py-3 text-[15px] text-ink" data-label="Code">
                                {{ $classroom->code ?? '—' }}
                            </td>

                            <td class="px-4 py-3" data-label="Status">
                                <x-badge tone="{{ $classroom->status === \App\Models\Classroom::STATUS_ACTIVE ? 'cyan' : 'dim' }}" class="uppercase">
                                    {{ $classroom->status }}
                                </x-badge>
                            </td>

                            <td class="px-4 py-3 text-[15px] text-ink" data-label="Students">
                                {{ $classroom->students_count }}
                            </td>

                            <td class="px-4 py-3 text-[15px] text-ink" data-label="Courses">
                                {{ $classroom->courses_count }}
                            </td>

                            <td class="px-4 py-3" data-label="Open">
                                <a href="{{ route('classrooms.show', $classroom) }}" class="text-sm font-bold text-cyan hover:underline">
                                    VIEW →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection