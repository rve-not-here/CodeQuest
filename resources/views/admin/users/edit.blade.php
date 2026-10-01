@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$user->username)

@section('content')
    <x-page-header
        title="Edit Account"
        subtitle="{{ $user->username }} — username, name, password, and role/status. Role and status changes are guarded: you cannot change your own role away from admin, deactivate yourself, or leave fewer than two active admins."
    >
        <x-slot:actions>
            <a href="{{ route('admin.users.show', $user) }}" class="btn-ghost">◀ CANCEL</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('error'))
        <x-status-message type="error" title="CHANGE REJECTED" class="mb-4">
            {{ session('error') }}
        </x-status-message>
    @endif

    @if ($errors->any())
        <x-status-message type="error" title="INPUT REJECTED" class="mb-4">
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </x-status-message>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="panel p-4 max-w-2xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="role" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Role
                </label>
                <select id="role" name="role" class="terminal-input">
                    @foreach (['student', 'teacher', 'admin', 'operator'] as $candidate)
                        <option value="{{ $candidate }}" @selected(old('role', $user->role) === $candidate)>
                            {{ strtoupper($candidate) }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs font-bold text-phosphor-dim mt-1">
                    FULL SET — OPERATOR ASSIGNABLE ON EXISTING ACCOUNTS
                </p>
            </div>

            <div>
                <label for="status" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Status
                </label>
                <select id="status" name="status" class="terminal-input">
                    <option value="active" @selected(old('status', $user->status) === 'active')>ACTIVE</option>
                    <option value="inactive" @selected(old('status', $user->status) === 'inactive')>INACTIVE</option>
                </select>
                <p class="text-xs font-bold text-phosphor-dim mt-1">
                    @if ($user->role === 'admin' && $user->status === 'active')
                        ADMIN — THE ACTIVE-ADMIN FLEET MUST STAY ≥ 2
                    @else
                        DEACTIVATION LOCKS THE ACCOUNT ON ITS NEXT REQUEST
                    @endif
                </p>
            </div>

            <div>
                <label for="username" class="block text-sm font-bold text-phosphor-dim mb-1">
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
                    value="{{ old('name', $user->name) }}"
                    required
                    maxlength="128"
                    class="terminal-input"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-phosphor-dim mb-1">
                    New password
                </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    class="terminal-input"
                >
                <p class="text-xs font-bold text-phosphor-dim mt-1">
                    LEAVE BLANK TO KEEP CURRENT + PLAINTEXT ONLY
                </p>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="btn-primary">SAVE ACCOUNT →</button>
            <a href="{{ route('admin.users.show', $user) }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
@endsection