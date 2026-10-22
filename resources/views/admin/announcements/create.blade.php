@extends('layouts.app', ['role' => $role])

@section('title', 'New announcement')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Platform scope</span>
        </div>

        <h1 class="sr-only">New announcement</h1>

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

        <section class="atlas-record-group" aria-labelledby="message-title">
            <div class="atlas-record-group-head">
                <h2 id="message-title" class="atlas-record-group-title">Message</h2>
                <p class="atlas-record-group-figure">Saved as a draft</p>
            </div>

            <form method="POST" action="{{ route('admin.announcements.store') }}" class="atlas-form">
                @csrf

                <div class="atlas-form-grid">
                    <div class="atlas-form-field atlas-form-field--wide">
                        <label for="title" class="atlas-lesson-entry-label">Title</label>
                        <input
                            id="title"
                            type="text"
                            name="title"
                            value="{{ old('title') }}"
                            required
                            maxlength="160"
                            class="terminal-input"
                         @if ($errors->has('title')) aria-invalid="true" aria-describedby="title-error" @endif>
                        @error('title')<p id="title-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field atlas-form-field--wide">
                        <label for="message" class="atlas-lesson-entry-label">Message</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            required
                            class="terminal-input"
                         @if ($errors->has('message')) aria-invalid="true" aria-describedby="message-error" @endif>{{ old('message') }}</textarea>
                        @error('message')<p id="message-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="audience" class="atlas-lesson-entry-label">Audience</label>
                        <select id="audience" name="audience" class="terminal-input" required aria-describedby="{{ $errors->has('audience') ? 'audience-help audience-error' : 'audience-help' }}" @if ($errors->has('audience')) aria-invalid="true" @endif>
                            <option value="all" @selected(old('audience') === 'all')>All students, teachers &amp; admins</option>
                            <option value="students" @selected(old('audience') === 'students')>Students</option>
                            <option value="teachers" @selected(old('audience') === 'teachers')>Teachers</option>
                            <option value="admins" @selected(old('audience') === 'admins')>Admins</option>
                        </select>
                        @error('audience')<p id="audience-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        <p id="audience-help" class="atlas-field-help">Server-determined · operators never delivered.</p>
                    </div>
                </div>

                <div class="atlas-form-actions">
                    <button type="submit" class="btn btn-primary">Save as draft →</button>
                    <a href="{{ route('admin.announcements') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
