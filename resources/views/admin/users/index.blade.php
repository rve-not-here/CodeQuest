@extends('layouts.app', ['role' => $role])

@section('title', 'User Management')

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>User Management</h1><p>Search accounts and manage roles and access.</p></div>
        <div class="admin-actions"><a href="{{ route('admin.users.create') }}" class="btn-primary">+ NEW USER</a></div>
    </header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <h2 class="text-sm font-bold text-accent mb-2">Account Directory</h2>

    <form method="GET" action="{{ route('admin.users') }}" class="admin-filters">
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
                    placeholder="USERNAME / NAME"
                    class="terminal-input"
                 @if ($errors->has('q')) aria-invalid="true" aria-describedby="q-error" @endif>
                @error('q')<p id="q-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-bold text-fg-muted mb-1">
                    Role
                </label>
                <select id="role" name="role" class="terminal-input" @if ($errors->has('role')) aria-invalid="true" aria-describedby="role-error" @endif>
                    <option value="">ALL ROLES</option>
                    <option value="student" @selected(($filters['role'] ?? null) === 'student')>STUDENT</option>
                    <option value="teacher" @selected(($filters['role'] ?? null) === 'teacher')>TEACHER</option>
                    <option value="admin" @selected(($filters['role'] ?? null) === 'admin')>ADMIN</option>
                    <option value="operator" @selected(($filters['role'] ?? null) === 'operator')>OPERATOR</option>
                </select>
                @error('role')<p id="role-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn-ghost">FILTER →</button>
                @if (! empty($filters['q']) || ! empty($filters['role']))
                    <a href="{{ route('admin.users') }}" class="btn-ghost">CLEAR</a>
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

    <p class="result-count mb-3 text-sm text-ink">{{ $users->total() }} {{ Str::plural('user', $users->total()) }}</p>

    @if ($users->isEmpty())
        <x-status-message type="info" title="NO USERS">
            No users match these filters.
        </x-status-message>
    @else
        <div class="admin-table-region">
            <table class="table-stack admin-table" role="table">
                <caption>Account directory</caption>
                <thead>
                    <tr>
                        <th scope="col">User</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col">Created</th>
                        <th scope="col">Last activity</th>
                        <th scope="col">Record</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $row)
                        @php
                            $roleTone = match ($row['role']) {
                                'admin' => 'cyan',
                                'teacher' => 'amber',
                                'operator' => 'dim',
                                default => 'phosphor',
                            };
                            $statusTone = $row['status'] === 'active' ? 'cyan' : 'amber';
                        @endphp

                        <tr>
                            <th scope="row" data-label="User">
                                <a
                                    href="{{ route('admin.users.show', $row['id']) }}"
                                    class="text-accent hover:underline"
                                >{{ $row['username'] }}</a>
                                @if ($row['name'])
                                    <span class="block text-[15px] text-fg-muted">{{ $row['name'] }}</span>
                                @endif
                            </th>

                            <td data-label="Role">
                                <x-badge tone="{{ $roleTone }}">{{ strtoupper($row['role']) }}</x-badge>
                            </td>

                            <td data-label="Status">
                                <x-badge tone="{{ $statusTone }}">{{ strtoupper($row['status']) }}</x-badge>
                            </td>

                            <td data-label="Created">
                                {{ $row['createdAt']->format('Y-m-d') }}
                            </td>

                            <td data-label="Last activity">
                                @if ($row['lastActivity'] !== null)
                                    <span class="block text-ink">{{ $row['lastActivity']['message'] }}</span>
                                    <span class="block text-[13px] text-fg-muted">{{ $row['lastActivity']['at'] }}</span>
                                @else
                                    <span class="text-fg-muted">—</span>
                                @endif
                            </td>

                            <td data-label="Record">
                                <a href="{{ route('admin.users.show', $row['id']) }}" class="text-sm font-bold text-info hover:underline">
                                    VIEW →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($users->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-4 gap-y-2 p-3 border-t border-fg-muted/60">
                    <p class="text-sm font-bold text-fg-muted">
                        SHOWING PAGE {{ $users->currentPage() }} OF {{ $users->lastPage() }}
                    </p>
                    <div class="flex items-center gap-2">
                        @if ($users->onFirstPage())
                            <span class="btn-ghost opacity-50 pointer-events-none">◀ PREV</span>
                        @else
                            <a href="{{ $users->previousPageUrl() }}" class="btn-ghost">◀ PREV</a>
                        @endif

                        @if ($users->hasMorePages())
                            <a href="{{ $users->nextPageUrl() }}" class="btn-ghost">NEXT ▶</a>
                        @else
                            <span class="btn-ghost opacity-50 pointer-events-none">NEXT ▶</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif
    </div>
@endsection
