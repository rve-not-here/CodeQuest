@extends('layouts.app', ['role' => $role])

@section('title', 'New user')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Users · Platform scope</span>
            <a href="{{ route('admin.users') }}">← All users</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Accounts</span>
            <h1 class="atlas-records-title">New user</h1>
            <p class="atlas-records-lede">Create an account and choose its role. The account is active from its first sign-in.</p>
        </header>

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
                <p class="atlas-record-group-figure">Identity and access</p>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="atlas-form">
                @csrf

                <div class="atlas-form-grid">
                    <div class="atlas-form-field">
                        <label for="username" class="atlas-lesson-entry-label">Username</label>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
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
                            value="{{ old('name') }}"
                            required
                            maxlength="128"
                            class="terminal-input"
                         @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                        @error('name')<p id="name-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="role" class="atlas-lesson-entry-label">Role</label>
                        <select id="role" name="role" class="terminal-input" required @if ($errors->has('role')) aria-invalid="true" aria-describedby="role-error" @endif>
                            <option value="student" @selected(old('role') === 'student')>Student</option>
                            <option value="teacher" @selected(old('role') === 'teacher')>Teacher</option>
                            <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                        </select>
                        @error('role')<p id="role-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="password" class="atlas-lesson-entry-label">Initial password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            class="terminal-input"
                         aria-describedby="{{ $errors->has('password') ? 'password-help password-error' : 'password-help' }}" @if ($errors->has('password')) aria-invalid="true" @endif>
                        @error('password')<p id="password-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        <p id="password-help" class="atlas-field-help">At least 8 characters.</p>
                    </div>
                </div>

                <div class="atlas-form-actions">
                    <button type="submit" class="btn btn-primary">Create account →</button>
                    <a href="{{ route('admin.users') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
