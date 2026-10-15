@extends('layouts.app', ['role' => $role])

@section('title', 'Edit Announcement')

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>Edit Announcement</h1><p>{{ $announcement->title }} · Changes do not resend a published announcement.</p></div>
        <div class="admin-actions"><a href="{{ route('admin.announcements') }}" class="btn-ghost">◀ ALL ANNOUNCEMENTS</a></div>
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

    <div class="flex flex-wrap items-center gap-2 mb-4">
        <x-badge tone="cyan">STATUS: {{ strtoupper($announcement->status) }}</x-badge>
        <x-badge tone="phosphor">AUDIENCE: {{ strtoupper($announcement->audience) }}</x-badge>
        @if ($announcement->published_at !== null)
            <x-badge tone="dim">PUBLISHED {{ $announcement->published_at->format('M d, H:i') }}</x-badge>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="admin-form">
        @csrf
        @method('PUT')

        <div>
            <label for="title" class="block text-sm font-bold text-fg-muted mb-1">
                Title
            </label>
            <input
                id="title"
                type="text"
                name="title"
                value="{{ old('title', $announcement->title) }}"
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
             @if ($errors->has('message')) aria-invalid="true" aria-describedby="message-error" @endif>{{ old('message', $announcement->message) }}</textarea>
                @error('message')<p id="message-error" class="admin-field-error">{{ $message }}</p>@enderror
        </div>

        <div class="mt-4 max-w-xs">
            <label for="audience" class="block text-sm font-bold text-fg-muted mb-1">
                Audience
            </label>
            <select id="audience" name="audience" class="terminal-input" required aria-describedby="{{ $errors->has('audience') ? 'audience-help audience-error' : 'audience-help' }}" @if ($errors->has('audience')) aria-invalid="true" @endif>
                <option value="all" @selected(old('audience', $announcement->audience) === 'all')>ALL STUDENTS, TEACHERS &amp; ADMINS</option>
                <option value="students" @selected(old('audience', $announcement->audience) === 'students')>STUDENTS</option>
                <option value="teachers" @selected(old('audience', $announcement->audience) === 'teachers')>TEACHERS</option>
                <option value="admins" @selected(old('audience', $announcement->audience) === 'admins')>ADMINS</option>
            </select>
                @error('audience')<p id="audience-error" class="admin-field-error">{{ $message }}</p>@enderror
            <p id="audience-help" class="text-xs font-bold text-fg-muted mt-1">
                SERVER-DETERMINED · OPERATORS NEVER DELIVERED
            </p>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="submit" class="btn-primary">SAVE ANNOUNCEMENT →</button>
            <a href="{{ route('admin.announcements') }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
    <section class="admin-consequential-actions" aria-labelledby="delivery-heading">
        <h2 id="delivery-heading">Announcement delivery</h2>
        <p>{{ $announcement->title }} · {{ strtoupper($announcement->status) }} · {{ strtoupper($announcement->audience) }}</p>
        <div class="admin-actions">
            @if ($announcement->status === 'draft')
                <form method="POST" action="{{ route('admin.announcements.publish', $announcement) }}">
                    @csrf
                    <button type="submit" class="btn-ghost">PUBLISH &amp; DELIVER →</button>
                </form>
            @endif
            @if ($announcement->status === 'published')
                <form method="POST" action="{{ route('admin.announcements.archive', $announcement) }}">
                    @csrf
                    <button type="submit" class="btn-ghost">ARCHIVE →</button>
                </form>
            @endif
        </div>
    </section>
    </div>
@endsection
