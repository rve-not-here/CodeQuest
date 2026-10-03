@extends('layouts.app', ['role' => $role])

@section('title', 'Classroom Management')

@section('content')
    <x-page-header
        title="Classroom Management"
        subtitle="Manage classrooms, teacher assignments, student enrollment, and course access."
        icon="▣"
    >
        <x-slot:actions>
            <a href="{{ route('admin.classrooms.create') }}" class="btn-primary">+ NEW CLASSROOM</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <h2 class="text-sm font-bold text-phosphor mb-2">Classroom Directory</h2>

    <form method="GET" action="{{ route('admin.classrooms') }}" class="panel p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label for="q" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Search
                </label>
                <input
                    id="q"
                    name="q"
                    type="search"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="NAME / CODE"
                    class="terminal-input"
                >
            </div>

            <div>
                <label for="status" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Status
                </label>
                <select id="status" name="status" class="terminal-input">
                    <option value="">ALL STATUSES</option>
                    <option value="active" @selected(($filters['status'] ?? null) === 'active')>ACTIVE</option>
                    <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>INACTIVE</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn-ghost">FILTER →</button>
                @if (! empty($filters['q']) || ! empty($filters['status']))
                    <a href="{{ route('admin.classrooms') }}" class="btn-ghost">CLEAR</a>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <x-status-message type="error" title="QUERY REJECTED" class="mt-3">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </x-status-message>
        @endif
    </form>

    <p class="result-count mb-3 text-sm text-ink">{{ $classrooms->count() }} {{ Str::plural('classroom', $classrooms->count()) }}</p>

    @if ($classrooms->isEmpty())
        <x-status-message type="info" title="NO CLASSROOMS">
            No classrooms match the current scan parameters.
        </x-status-message>
    @else
        <div class="panel p-0 overflow-x-auto">
            <table class="table-stack w-full text-left">
                <thead>
                    <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                        <th class="px-4 py-3">Classroom</th>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Teachers</th>
                        <th class="px-4 py-3">Students</th>
                        <th class="px-4 py-3">Courses</th>
                        <th class="px-4 py-3">Edit</th>
                    </tr>
                </thead>
                <tbody class="font-body text-lg">
                    @foreach ($classrooms as $classroom)
                        <tr class="border-b border-phosphor-dim/40 align-top">
                            <td class="px-4 py-3" data-label="Classroom">
                                <a href="{{ route('admin.classrooms.edit', $classroom) }}" class="text-phosphor hover:underline">
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

                            <td class="px-4 py-3 text-[15px] text-ink" data-label="Teachers">
                                {{ $classroom->teachers_count }}
                            </td>

                            <td class="px-4 py-3 text-[15px] text-ink" data-label="Students">
                                {{ $classroom->students_count }}
                            </td>

                            <td class="px-4 py-3 text-[15px] text-ink" data-label="Courses">
                                {{ $classroom->courses_count }}
                            </td>

                            <td class="px-4 py-3" data-label="Edit">
                                <a href="{{ route('admin.classrooms.edit', $classroom) }}" class="text-sm font-bold text-cyan hover:underline">
                                    EDIT →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $classrooms->links() }}
        </div>
    @endif
@endsection