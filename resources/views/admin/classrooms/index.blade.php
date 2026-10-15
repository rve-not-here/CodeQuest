@extends('layouts.app', ['role' => $role])

@section('title', 'Classroom Management')

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>Classroom Management</h1><p>Manage classrooms, teacher assignments, student enrollment, and course access.</p></div>
        <div class="admin-actions"><a href="{{ route('admin.classrooms.create') }}" class="btn-primary">+ NEW CLASSROOM</a></div>
    </header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <h2 class="text-sm font-bold text-accent mb-2">Classroom Directory</h2>

    <form method="GET" action="{{ route('admin.classrooms') }}" class="admin-filters">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label for="q" class="block text-sm font-bold text-fg-muted mb-1">
                    Search
                </label>
                <input
                    id="q"
                    name="q"
                    type="search"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="NAME / CODE"
                    class="terminal-input"
                 @if ($errors->has('q')) aria-invalid="true" aria-describedby="q-error" @endif>
                @error('q')<p id="q-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-bold text-fg-muted mb-1">
                    Status
                </label>
                <select id="status" name="status" class="terminal-input" @if ($errors->has('status')) aria-invalid="true" aria-describedby="status-error" @endif>
                    <option value="">ALL STATUSES</option>
                    <option value="active" @selected(($filters['status'] ?? null) === 'active')>ACTIVE</option>
                    <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>INACTIVE</option>
                </select>
                @error('status')<p id="status-error" class="admin-field-error">{{ $message }}</p>@enderror
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
            No classrooms match these filters.
        </x-status-message>
    @else
        <div class="admin-table-region">
            <table class="table-stack admin-table" role="table">
                <caption>Classroom directory</caption>
                <thead>
                    <tr>
                        <th scope="col">Classroom</th>
                        <th scope="col">Code</th>
                        <th scope="col">Status</th>
                        <th scope="col">Teachers</th>
                        <th scope="col">Students</th>
                        <th scope="col">Courses</th>
                        <th scope="col">Edit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($classrooms as $classroom)
                        <tr>
                            <th scope="row" data-label="Classroom">
                                <a href="{{ route('admin.classrooms.edit', $classroom) }}" class="text-accent hover:underline">
                                    {{ $classroom->name }}
                                </a>
                            </th>

                            <td data-label="Code">
                                {{ $classroom->code ?? '—' }}
                            </td>

                            <td data-label="Status">
                                <x-badge tone="{{ $classroom->status === \App\Models\Classroom::STATUS_ACTIVE ? 'cyan' : 'dim' }}" class="uppercase">
                                    {{ $classroom->status }}
                                </x-badge>
                            </td>

                            <td data-label="Teachers">
                                {{ $classroom->teachers_count }}
                            </td>

                            <td data-label="Students">
                                {{ $classroom->students_count }}
                            </td>

                            <td data-label="Courses">
                                {{ $classroom->courses_count }}
                            </td>

                            <td data-label="Edit">
                                <a href="{{ route('admin.classrooms.edit', $classroom) }}" class="text-sm font-bold text-info hover:underline">
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
    </div>
@endsection
