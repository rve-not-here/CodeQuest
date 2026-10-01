@extends('layouts.app', ['role' => $role])

@section('title', 'Edit Announcement')

@section('content')
    <x-page-header
        title="Edit Announcement"
        subtitle="{{ $announcement->title }} — editing a published announcement never re-delivers it; already-notified users keep the version they were sent."
    >
        <x-slot:actions>
            <a href="{{ route('admin.announcements') }}" class="btn-ghost">◀ ALL ANNOUNCEMENTS</a>
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

    <div class="flex flex-wrap items-center gap-2 mb-4">
        <x-badge tone="cyan">STATUS: {{ strtoupper($announcement->status) }}</x-badge>
        <x-badge tone="phosphor">AUDIENCE: {{ strtoupper($announcement->audience) }}</x-badge>
        @if ($announcement->published_at !== null)
            <x-badge tone="dim">PUBLISHED {{ $announcement->published_at->format('M d, H:i') }}</x-badge>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="panel p-4 max-w-2xl">
        @csrf
        @method('PUT')

        <div>
            <label for="title" class="block text-sm font-bold text-phosphor-dim mb-1">
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
            >
        </div>

        <div class="mt-4">
            <label for="message" class="block text-sm font-bold text-phosphor-dim mb-1">
                Message
            </label>
            <textarea
                id="message"
                name="message"
                rows="6"
                required
                class="terminal-input"
            >{{ old('message', $announcement->message) }}</textarea>
        </div>

        <div class="mt-4 max-w-xs">
            <label for="audience" class="block text-sm font-bold text-phosphor-dim mb-1">
                Audience
            </label>
            <select id="audience" name="audience" class="terminal-input" required>
                <option value="all" @selected(old('audience', $announcement->audience) === 'all')>ALL STUDENTS, TEACHERS &amp; ADMINS</option>
                <option value="students" @selected(old('audience', $announcement->audience) === 'students')>STUDENTS</option>
                <option value="teachers" @selected(old('audience', $announcement->audience) === 'teachers')>TEACHERS</option>
                <option value="admins" @selected(old('audience', $announcement->audience) === 'admins')>ADMINS</option>
            </select>
            <p class="text-xs font-bold text-phosphor-dim mt-1">
                SERVER-DETERMINED · OPERATORS NEVER DELIVERED
            </p>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="submit" class="btn-primary">SAVE ANNOUNCEMENT →</button>
            <a href="{{ route('admin.announcements') }}" class="btn-ghost">CANCEL</a>
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
    </form>
@endsection