@extends('layouts.app', ['role' => $role])

@section('title', 'New Announcement')

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>New Announcement</h1><p>Write a draft and choose its audience. Publishing delivers the message.</p></div>
        <div class="admin-actions"><a href="{{ route('admin.announcements') }}" class="btn-ghost">◀ ALL ANNOUNCEMENTS</a></div>
    </header>

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

    <form method="POST" action="{{ route('admin.announcements.store') }}" class="admin-form">
        @csrf

        <div>
            <label for="title" class="block text-sm font-bold text-fg-muted mb-1">
                Title
            </label>
            <input
                id="title"
                type="text"
                name="title"
                value="{{ old('title') }}"
                required
                maxlength="160"
                class="terminal-input"
             @if ($errors->has('title')) aria-invalid="true" aria-describedby="title-error" @endif>
                @error('title')<p id="title-error" class="admin-field-error">{{ $message }}</p>@enderror
        </div>

        <div class="mt-4">
            <label for="message" class="block text-sm font-bold text-fg-muted mb-1">
                Message
            </label>
            <textarea
                id="message"
                name="message"
                rows="6"
                required
                class="terminal-input"
             @if ($errors->has('message')) aria-invalid="true" aria-describedby="message-error" @endif>{{ old('message') }}</textarea>
                @error('message')<p id="message-error" class="admin-field-error">{{ $message }}</p>@enderror
        </div>

        <div class="mt-4 max-w-xs">
            <label for="audience" class="block text-sm font-bold text-fg-muted mb-1">
                Audience
            </label>
            <select id="audience" name="audience" class="terminal-input" required aria-describedby="{{ $errors->has('audience') ? 'audience-help audience-error' : 'audience-help' }}" @if ($errors->has('audience')) aria-invalid="true" @endif>
                <option value="all" @selected(old('audience') === 'all')>ALL STUDENTS, TEACHERS &amp; ADMINS</option>
                <option value="students" @selected(old('audience') === 'students')>STUDENTS</option>
                <option value="teachers" @selected(old('audience') === 'teachers')>TEACHERS</option>
                <option value="admins" @selected(old('audience') === 'admins')>ADMINS</option>
            </select>
                @error('audience')<p id="audience-error" class="admin-field-error">{{ $message }}</p>@enderror
            <p id="audience-help" class="text-xs font-bold text-fg-muted mt-1">
                SERVER-DETERMINED · OPERATORS NEVER DELIVERED
            </p>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn-primary">SAVE AS DRAFT →</button>
            <a href="{{ route('admin.announcements') }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
    </div>
@endsection
