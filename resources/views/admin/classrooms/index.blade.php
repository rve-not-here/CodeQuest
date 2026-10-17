@extends('layouts.app', ['role' => $role])

@section('title', 'Classroom Management')

@section('content')
    <div class="admin-management">
    <x-page-header title="Classroom Management" subtitle="Manage classrooms, teacher assignments, student enrollment, and course access.">
        <x-slot:actions>
            <a href="{{ route('admin.classrooms.create') }}" class="btn-primary">+ NEW CLASSROOM</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <x-panel title="Classroom Directory">
        <form method="GET" action="{{ route('admin.classrooms') }}" class="admin-toolbar">
            <div class="admin-toolbar-field admin-toolbar-field--wide">
                <label for="q">Search</label>
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

            <div class="admin-toolbar-field">
                <label for="status">Status</label>
                <select id="status" name="status" class="terminal-input" @if ($errors->has('status')) aria-invalid="true" aria-describedby="status-error" @endif>
                    <option value="">ALL STATUSES</option>
                    <option value="active" @selected(($filters['status'] ?? null) === 'active')>ACTIVE</option>
                    <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>INACTIVE</option>
                </select>
                @error('status')<p id="status-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="admin-toolbar-actions">
                <button type="submit" class="btn-ghost">FILTER →</button>
                @if (! empty($filters['q']) || ! empty($filters['status']))
                    <a href="{{ route('admin.classrooms') }}" class="btn-ghost">CLEAR</a>
                @endif
            </div>

            <p class="admin-toolbar-count">{{ $classrooms->total() }} {{ Str::plural('classroom', $classrooms->total()) }}</p>
        </form>

        @if ($errors->any())
            <x-status-message type="error" title="QUERY REJECTED" class="mt-3">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </x-status-message>
        @endif

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
                            <th scope="col">Actions</th>
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

                                <td data-label="Actions">
                                    <a href="{{ route('admin.classrooms.edit', $classroom) }}" class="admin-row-link">
                                        EDIT →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($classrooms->hasPages())
                    <div class="panel-pagination">
                        <p>SHOWING PAGE {{ $classrooms->currentPage() }} OF {{ $classrooms->lastPage() }}</p>
                        <div class="flex items-center gap-2">
                            @if ($classrooms->onFirstPage())
                                <span class="btn-ghost opacity-50 pointer-events-none">◀ PREV</span>
                            @else
                                <a href="{{ $classrooms->previousPageUrl() }}" class="btn-ghost">◀ PREV</a>
                            @endif

                            @if ($classrooms->hasMorePages())
                                <a href="{{ $classrooms->nextPageUrl() }}" class="btn-ghost">NEXT ▶</a>
                            @else
                                <span class="btn-ghost opacity-50 pointer-events-none">NEXT ▶</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </x-panel>
    </div>
@endsection
