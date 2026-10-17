@extends('layouts.app', ['role' => $role])

@section('title', 'Create User')

@section('content')
    <div class="admin-management">
    <nav class="admin-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('admin.users') }}">Users</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Create User</span>
    </nav>

    <x-page-header title="Create User" subtitle="Create an account and choose its role." />

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

    <x-panel title="Account">
        <form method="POST" action="{{ route('admin.users.store') }}" class="admin-form">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="username" class="block text-sm font-bold text-fg-muted mb-1">
                        Username
                    </label>
                    <input
                        id="username"
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
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
                        value="{{ old('name') }}"
                        required
                        maxlength="128"
                        class="terminal-input"
                     @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                    @error('name')<p id="name-error" class="admin-field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="role" class="block text-sm font-bold text-fg-muted mb-1">
                        Role
                    </label>
                    <select id="role" name="role" class="terminal-input" required @if ($errors->has('role')) aria-invalid="true" aria-describedby="role-error" @endif>
                        <option value="student" @selected(old('role') === 'student')>STUDENT</option>
                        <option value="teacher" @selected(old('role') === 'teacher')>TEACHER</option>
                        <option value="admin" @selected(old('role') === 'admin')>ADMIN</option>
                    </select>
                    @error('role')<p id="role-error" class="admin-field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-fg-muted mb-1">
                        Initial password
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        class="terminal-input"
                     aria-describedby="{{ $errors->has('password') ? 'password-help password-error' : 'password-help' }}" @if ($errors->has('password')) aria-invalid="true" @endif>
                    @error('password')<p id="password-error" class="admin-field-error">{{ $message }}</p>@enderror
                    <p id="password-help" class="admin-field-help">
                        At least 8 characters.
                    </p>
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="btn-primary">CREATE ACCOUNT →</button>
                <a href="{{ route('admin.users') }}" class="btn-ghost">CANCEL</a>
            </div>
        </form>
    </x-panel>
    </div>
@endsection
