@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$user->username)

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Users · Platform scope</span>
            <div class="atlas-records-running-links">
                <a href="{{ route('admin.users.show', $user) }}">← {{ $user->username }}</a>
                <a href="{{ route('admin.users') }}">All users →</a>
            </div>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Accounts</span>
            <h1 class="atlas-records-title">Edit account</h1>
            <p class="atlas-records-lede">{{ $user->username }} · Update account details and access. Role and status changes are recorded in the audit trail.</p>
        </header>

        @if (session('error'))
            <div class="atlas-records-alerts">
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Change rejected</strong>
                    <span class="text-fg-muted">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="atlas-records-alerts">
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Input rejected</strong>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->messages() as $field => $messages)
                            @foreach ($messages as $error)
                                <li><a href="#{{ Str::before($field, '.') }}" class="underline underline-offset-2">{{ $error }}</a></li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section class="atlas-record-group" aria-labelledby="account-title">
            <div class="atlas-record-group-head">
                <h2 id="account-title" class="atlas-record-group-title">Account</h2>
                <p class="atlas-record-group-figure">{{ ucfirst($user->role) }} · {{ $user->status === 'active' ? 'Active' : 'Inactive' }}</p>
            </div>

            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="atlas-form">
                @csrf
                @method('PUT')

                <div class="atlas-form-grid">
                    <div class="atlas-form-field">
                        <label for="role" class="atlas-lesson-entry-label">Role</label>
                        <select id="role" name="role" class="terminal-input" aria-describedby="{{ $errors->has('role') ? 'role-help role-error' : 'role-help' }}" @if ($errors->has('role')) aria-invalid="true" @endif>
                            @foreach (['student', 'teacher', 'admin', 'operator'] as $candidate)
                                <option value="{{ $candidate }}" @selected(old('role', $user->role) === $candidate)>
                                    {{ ucfirst($candidate) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')<p id="role-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        <p id="role-help" class="atlas-field-help">Full set — operator is assignable on existing accounts only.</p>
                    </div>

                    <div class="atlas-form-field">
                        <label for="status" class="atlas-lesson-entry-label">Status</label>
                        <select id="status" name="status" class="terminal-input" aria-describedby="{{ $errors->has('status') ? 'status-help status-error' : 'status-help' }}" @if ($errors->has('status')) aria-invalid="true" @endif>
                            <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive</option>
                        </select>
                        @error('status')<p id="status-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        <p id="status-help" class="atlas-field-help">
                            @if ($user->role === 'admin' && $user->status === 'active')
                                Admin — the active-admin fleet must stay at 2 or more.
                            @else
                                Deactivation locks the account on its next request.
                            @endif
                        </p>
                    </div>

                    <div class="atlas-form-field">
                        <label for="username" class="atlas-lesson-entry-label">Username</label>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="{{ old('username', $user->username) }}"
                            required
                            maxlength="64"
                            class="terminal-input"
                            @if ($errors->has('username')) aria-invalid="true" aria-describedby="username-error" @endif>
                        @error('username')<p id="username-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="name" class="atlas-lesson-entry-label">Name</label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            required
                            maxlength="128"
                            class="terminal-input"
                            @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                        @error('name')<p id="name-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="password" class="atlas-lesson-entry-label">New password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            minlength="8"
                            autocomplete="new-password"
                            class="terminal-input"
                         aria-describedby="{{ $errors->has('password') ? 'password-help password-error' : 'password-help' }}" @if ($errors->has('password')) aria-invalid="true" @endif>
                        @error('password')<p id="password-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        <p id="password-help" class="atlas-field-help">Leave blank to keep the current password.</p>
                    </div>
                </div>

                <div class="atlas-form-actions">
                    <button type="submit" class="btn btn-primary">Save account →</button>
                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
