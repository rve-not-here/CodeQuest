@extends('layouts.app', ['role' => $role])

@section('title', 'New Announcement')

@section('content')
    <x-page-header
        title="New Announcement"
        subtitle="Write a draft and choose its audience. Publishing delivers the message."
        icon="◉"
    >
        <x-slot:actions>
            <a href="{{ route('admin.announcements') }}" class="btn-ghost">◀ ALL ANNOUNCEMENTS</a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <x-status-message type="error" title="INPUT REJECTED" class="mb-4">
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </x-status-message>
    @endif

    <form method="POST" action="{{ route('admin.announcements.store') }}" class="panel p-4 max-w-2xl">
        @csrf

        <div>
            <label for="title" class="block text-sm font-bold text-phosphor-dim mb-1">
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
            >{{ old('message') }}</textarea>
        </div>

        <div class="mt-4 max-w-xs">
            <label for="audience" class="block text-sm font-bold text-phosphor-dim mb-1">
                Audience
            </label>
            <select id="audience" name="audience" class="terminal-input" required>
                <option value="all" @selected(old('audience') === 'all')>ALL STUDENTS, TEACHERS &amp; ADMINS</option>
                <option value="students" @selected(old('audience') === 'students')>STUDENTS</option>
                <option value="teachers" @selected(old('audience') === 'teachers')>TEACHERS</option>
                <option value="admins" @selected(old('audience') === 'admins')>ADMINS</option>
            </select>
            <p class="text-xs font-bold text-phosphor-dim mt-1">
                SERVER-DETERMINED · OPERATORS NEVER DELIVERED
            </p>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="btn-primary">SAVE AS DRAFT →</button>
            <a href="{{ route('admin.announcements') }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
@endsection