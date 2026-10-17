@extends('layouts.app', ['role' => $role])

@section('title', 'New Classroom')

@section('content')
    <div class="admin-management">
    <nav class="admin-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('admin.classrooms') }}">Classrooms</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">New Classroom</span>
    </nav>

    <x-page-header title="New Classroom" subtitle="Create a classroom, then assign teachers, students, and courses." />

    @if (session('error'))
        <x-status-message type="error" title="WRITE REFUSED" class="mb-4">
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

    <x-panel title="Classroom">
        <form method="POST" action="{{ route('admin.classrooms.store') }}" class="admin-form">
            @csrf

            <div class="mb-4">
                <label for="name" class="block text-sm font-bold text-fg-muted mb-1">Name</label>
                <input id="name" name="name" type="text" maxlength="128" value="{{ old('name') }}" required class="terminal-input" @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                @error('name')<p id="name-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mb-4">
                <label for="code" class="block text-sm font-bold text-fg-muted mb-1">Code (optional)</label>
                <input id="code" name="code" type="text" maxlength="32" value="{{ old('code') }}" class="terminal-input" @if ($errors->has('code')) aria-invalid="true" aria-describedby="code-error" @endif>
                @error('code')<p id="code-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mb-6">
                <label for="status" class="block text-sm font-bold text-fg-muted mb-1">Status</label>
                <select id="status" name="status" class="terminal-input" aria-describedby="{{ $errors->has('status') ? 'status-help status-error' : 'status-help' }}" @if ($errors->has('status')) aria-invalid="true" @endif>
                    <option value="active" @selected(old('status', \App\Models\Classroom::STATUS_ACTIVE) === 'active')>ACTIVE</option>
                    <option value="inactive" @selected(old('status') === 'inactive')>INACTIVE</option>
                </select>
                @error('status')<p id="status-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="status-help" class="admin-field-help">
                    Status is a visibility boundary: only ACTIVE classrooms are visible to their teachers.
                </p>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="btn-primary">CREATE CLASSROOM →</button>
                <a href="{{ route('admin.classrooms') }}" class="btn-ghost">CANCEL</a>
            </div>
        </form>
    </x-panel>
    </div>
@endsection
