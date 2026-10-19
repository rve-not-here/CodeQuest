@extends('layouts.app', ['role' => $role])

@section('title', 'Users')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Users · Platform scope</span>
            <span class="atlas-stamp atlas-stamp--ink">System-wide</span>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Accounts</span>
            <h1 class="atlas-records-title">Users</h1>
            <p class="atlas-records-lede">Search accounts and manage roles and access. Every role and status change is recorded in the audit trail.</p>
        </header>

        @if (session('status'))
            <div class="atlas-records-alerts">
                <div class="panel border-success/40 bg-success-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-success">Saved</strong>
                    <span class="text-fg-muted">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <section class="atlas-record-group" aria-labelledby="accounts-title">
            <div class="atlas-record-group-head">
                <h2 id="accounts-title" class="atlas-record-group-title">Account directory</h2>
                <p class="atlas-record-group-figure">{{ $users->total() }} {{ Str::plural('account', $users->total()) }} {{ $users->total() === 1 ? 'matches' : 'match' }} the current filters</p>
            </div>

            <form method="GET" action="{{ route('admin.users') }}" class="atlas-index-filters" aria-label="Account filters">
                <div class="atlas-index-filter">
                    <label for="q">Search</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Username or name"
                        class="terminal-input"
                     @if ($errors->has('q')) aria-invalid="true" aria-describedby="q-error" @endif>
                    @error('q')<p id="q-error" class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="atlas-index-filter">
                    <label for="role">Role</label>
                    <select id="role" name="role" class="terminal-input" @if ($errors->has('role')) aria-invalid="true" aria-describedby="role-error" @endif>
                        <option value="">All roles</option>
                        <option value="student" @selected(($filters['role'] ?? null) === 'student')>Student</option>
                        <option value="teacher" @selected(($filters['role'] ?? null) === 'teacher')>Teacher</option>
                        <option value="admin" @selected(($filters['role'] ?? null) === 'admin')>Admin</option>
                        <option value="operator" @selected(($filters['role'] ?? null) === 'operator')>Operator</option>
                    </select>
                    @error('role')<p id="role-error" class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="atlas-index-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply filters</button>
                    @if (! empty($filters['q']) || ! empty($filters['role']))
                        <a href="{{ route('admin.users') }}" class="btn btn-ghost">Reset filters</a>
                    @endif
                    <a href="{{ route('admin.users.create') }}" class="btn btn-secondary">New user →</a>
                </div>
            </form>

            @if ($errors->any())
                <div class="atlas-index-alerts">
                    <div class="panel border-warning/40 bg-warning-soft px-4 py-3 text-sm" role="alert">
                        <strong class="text-warning">Query rejected</strong>
                        <span class="text-fg-muted">{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            @if ($users->isEmpty())
                <section class="atlas-records-empty" aria-labelledby="empty-accounts-title">
                    <h3 id="empty-accounts-title" class="atlas-records-empty-title">No accounts match</h3>
                    <p class="atlas-records-empty-copy">No users match these filters.</p>
                    @if (! empty($filters['q']) || ! empty($filters['role']))
                        <a href="{{ route('admin.users') }}" class="btn btn-ghost">Reset filters →</a>
                    @endif
                </section>
            @else
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Account directory</caption>
                        <thead>
                            <tr>
                                <th scope="col">User</th>
                                <th scope="col">Role</th>
                                <th scope="col">Status</th>
                                <th scope="col">Created</th>
                                <th scope="col">Last activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $row)
                                @php
                                    $roleTone = match ($row['role']) {
                                        'admin' => 'atlas-stamp--acc',
                                        'teacher' => 'atlas-stamp--amb',
                                        'operator' => 'atlas-stamp--ink',
                                        default => 'atlas-stamp--vio',
                                    };
                                    $roleLabel = match ($row['role']) {
                                        'admin' => 'Admin',
                                        'teacher' => 'Teacher',
                                        'operator' => 'Operator',
                                        default => 'Student',
                                    };
                                @endphp

                                <tr>
                                    <th scope="row" data-label="User">
                                        <a href="{{ route('admin.users.show', $row['id']) }}">{{ $row['username'] }}</a>
                                        @if ($row['name'])
                                            <span class="atlas-register-note">{{ $row['name'] }}</span>
                                        @endif
                                    </th>
                                    <td data-label="Role"><span class="atlas-stamp {{ $roleTone }}">{{ $roleLabel }}</span></td>
                                    <td data-label="Status"><span class="atlas-stamp {{ $row['status'] === 'active' ? 'atlas-stamp--grn' : 'atlas-stamp--ink' }}">{{ $row['status'] === 'active' ? 'Active' : 'Inactive' }}</span></td>
                                    <td data-label="Created">{{ $row['createdAt']->format('M d, Y') }}</td>
                                    <td data-label="Last activity">
                                        @if ($row['lastActivity'] !== null)
                                            <span>{{ $row['lastActivity']['message'] }}</span>
                                            <span class="atlas-register-note">{{ $row['lastActivity']['at'] }}</span>
                                        @else
                                            <span class="atlas-register-note">No activity recorded</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <nav class="atlas-records-pager" aria-label="Account directory pages">
                        <p class="atlas-records-pager-figure">Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }} · Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</p>
                        <div class="atlas-records-pager-actions">
                            @if ($users->onFirstPage())
                                <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">← Previous</span>
                            @else
                                <a href="{{ $users->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Previous</a>
                            @endif
                            @if ($users->hasMorePages())
                                <a href="{{ $users->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                            @else
                                <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">Next →</span>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </section>
    </div>
@endsection
