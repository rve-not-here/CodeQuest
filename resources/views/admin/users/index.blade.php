@extends('layouts.app', ['role' => $role])

@section('title', 'User Management')

@section('content')
    <x-page-header
        title="User Management"
        subtitle="Search accounts and manage roles and access."
        icon="☷"
    >
        <x-slot:actions>
            <a href="{{ route('admin.users.create') }}" class="btn-primary">+ NEW USER</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <h2 class="text-sm font-bold text-phosphor mb-2">Account Directory</h2>

    <form method="GET" action="{{ route('admin.users') }}" class="panel p-4 mb-4">
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
                    placeholder="USERNAME / NAME"
                    class="terminal-input"
                >
            </div>

            <div>
                <label for="role" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Role
                </label>
                <select id="role" name="role" class="terminal-input">
                    <option value="">ALL ROLES</option>
                    <option value="student" @selected(($filters['role'] ?? null) === 'student')>STUDENT</option>
                    <option value="teacher" @selected(($filters['role'] ?? null) === 'teacher')>TEACHER</option>
                    <option value="admin" @selected(($filters['role'] ?? null) === 'admin')>ADMIN</option>
                    <option value="operator" @selected(($filters['role'] ?? null) === 'operator')>OPERATOR</option>
                </select>
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
            No users match the current scan parameters.
        </x-status-message>
    @else
        <div class="panel p-0 overflow-x-auto">
            <table class="table-stack w-full text-left">
                <thead>
                    <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3">Last activity</th>
                        <th class="px-4 py-3">Record</th>
                    </tr>
                </thead>
                <tbody class="font-body text-lg">
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

                        <tr class="border-b border-phosphor-dim/40 align-top">
                            <td class="px-4 py-3" data-label="User">
                                <a
                                    href="{{ route('admin.users.show', $row['id']) }}"
                                    class="text-phosphor hover:underline"
                                >{{ $row['username'] }}</a>
                                @if ($row['name'])
                                    <span class="block text-[15px] text-phosphor-dim">{{ $row['name'] }}</span>
                                @endif
                            </td>

                            <td class="px-4 py-3" data-label="Role">
                                <x-badge tone="{{ $roleTone }}">{{ strtoupper($row['role']) }}</x-badge>
                            </td>

                            <td class="px-4 py-3" data-label="Status">
                                <x-badge tone="{{ $statusTone }}">{{ strtoupper($row['status']) }}</x-badge>
                            </td>

                            <td class="px-4 py-3 text-[15px] text-ink" data-label="Created">
                                {{ $row['createdAt']->format('Y-m-d') }}
                            </td>

                            <td class="px-4 py-3 text-[15px]" data-label="Last activity">
                                @if ($row['lastActivity'] !== null)
                                    <span class="block text-ink">{{ $row['lastActivity']['message'] }}</span>
                                    <span class="block text-[13px] text-phosphor-dim">{{ $row['lastActivity']['at'] }}</span>
                                @else
                                    <span class="text-phosphor-dim">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3" data-label="Record">
                                <a href="{{ route('admin.users.show', $row['id']) }}" class="text-sm font-bold text-cyan hover:underline">
                                    VIEW →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($users->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-4 gap-y-2 p-3 border-t border-phosphor-dim/60">
                    <p class="text-sm font-bold text-phosphor-dim">
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
@endsection