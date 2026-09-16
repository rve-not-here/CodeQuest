@extends('layouts.app', ['role' => $role])

@section('title', 'Create User')

@section('content')
    <x-page-header
        title="Create User"
        subtitle="Server assigns the role and the default active status; the password is submitted as plaintext and stored as a fresh hash."
        icon="☷"
    >
        <x-slot:actions>
            <a href="{{ route('admin.users') }}" class="btn-ghost">◀ ALL USERS</a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <x-status-message type="error" title="INPUT REJECTED" class="mb-4">
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </x-status-message>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}" class="panel p-4 max-w-2xl">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="username" class="block text-sm font-bold text-phosphor-dim mb-1">
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
                >
            </div>

            <div>
                <label for="name" class="block text-sm font-bold text-phosphor-dim mb-1">
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
                >
            </div>

            <div>
                <label for="role" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Role
                </label>
                <select id="role" name="role" class="terminal-input" required>
                    <option value="student" @selected(old('role') === 'student')>STUDENT</option>
                    <option value="teacher" @selected(old('role') === 'teacher')>TEACHER</option>
                    <option value="admin" @selected(old('role') === 'admin')>ADMIN</option>
                </select>
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-phosphor-dim mb-1">
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
                >
                <p class="text-xs font-bold text-phosphor-dim mt-1">
                    MIN 8 CHARS · PLAINTEXT ONLY
                </p>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="btn-primary">CREATE ACCOUNT →</button>
            <a href="{{ route('admin.users') }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
@endsection