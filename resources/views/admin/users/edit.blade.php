@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$user->username)

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>Edit Account</h1><p>{{ $user->username }} · Update account details and access.</p></div>
        <div class="admin-actions"><a href="{{ route('admin.users.show', $user) }}" class="btn-ghost">◀ CANCEL</a></div>
    </header>

    @if (session('error'))
        <x-status-message type="error" title="CHANGE REJECTED" class="mb-4">
            {{ session('error') }}
        </x-status-message>
    @endif

    @if ($errors->any())
        <x-status-message type="error" title="INPUT REJECTED" class="mb-4">
            <ul>
                @foreach ($errors->messages() as $field => $messages)
                    @foreach ($messages as $error)
                        <li><a href="#{{ Str::before($field, '.') }}">{{ $error }}</a></li>
                    @endforeach
                @endforeach
            </ul>
        </x-status-message>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="admin-form">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="role" class="block text-sm font-bold text-fg-muted mb-1">
                    Role
                </label>
                <select id="role" name="role" class="terminal-input" aria-describedby="{{ $errors->has('role') ? 'role-help role-error' : 'role-help' }}" @if ($errors->has('role')) aria-invalid="true" @endif>
                    @foreach (['student', 'teacher', 'admin', 'operator'] as $candidate)
                        <option value="{{ $candidate }}" @selected(old('role', $user->role) === $candidate)>
                            {{ strtoupper($candidate) }}
                        </option>
                    @endforeach
                </select>
                @error('role')<p id="role-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="role-help" class="text-xs font-bold text-fg-muted mt-1">
                    FULL SET — OPERATOR ASSIGNABLE ON EXISTING ACCOUNTS
                </p>
            </div>

            <div>
                <label for="status" class="block text-sm font-bold text-fg-muted mb-1">
                    Status
                </label>
                <select id="status" name="status" class="terminal-input" aria-describedby="{{ $errors->has('status') ? 'status-help status-error' : 'status-help' }}" @if ($errors->has('status')) aria-invalid="true" @endif>
                    <option value="active" @selected(old('status', $user->status) === 'active')>ACTIVE</option>
                    <option value="inactive" @selected(old('status', $user->status) === 'inactive')>INACTIVE</option>
                </select>
                @error('status')<p id="status-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="status-help" class="text-xs font-bold text-fg-muted mt-1">
                    @if ($user->role === 'admin' && $user->status === 'active')
                        ADMIN — THE ACTIVE-ADMIN FLEET MUST STAY ≥ 2
                    @else
                        DEACTIVATION LOCKS THE ACCOUNT ON ITS NEXT REQUEST
                    @endif
                </p>
            </div>

            <div>
                <label for="username" class="block text-sm font-bold text-fg-muted mb-1">
                    Username
                </label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="{{ old('username', $user->username) }}"
                    required
                    maxlength="64"
                    class="terminal-input"
                    @if ($errors->has('username')) aria-invalid="true" aria-describedby="username-error" @endif>
                @error('username')<p id="username-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-bold text-fg-muted mb-1">
                    Name
                </label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $user->name) }}"
                    required
                    maxlength="128"
                    class="terminal-input"
                    @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                @error('name')<p id="name-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-fg-muted mb-1">
                    New password
                </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    class="terminal-input"
                 aria-describedby="{{ $errors->has('password') ? 'password-help password-error' : 'password-help' }}" @if ($errors->has('password')) aria-invalid="true" @endif>
                @error('password')<p id="password-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="password-help" class="text-xs font-bold text-fg-muted mt-1">
                    Leave blank to keep the current password.
                </p>
            </div>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn-primary">SAVE ACCOUNT →</button>
            <a href="{{ route('admin.users.show', $user) }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
    </div>
@endsection
