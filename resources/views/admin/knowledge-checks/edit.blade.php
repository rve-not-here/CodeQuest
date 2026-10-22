@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$check->title)

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · {{ $course->name }}</span>
            <div class="atlas-records-running-links">
                <a href="{{ route('admin.courses.missions.checks', [$course, $mission]) }}">← Knowledge Checks</a>
                <a href="{{ route('admin.courses.missions.edit', [$course, $mission]) }}">{{ $mission->title }} →</a>
            </div>
        </div>

        <h1 class="sr-only">Edit Knowledge Check</h1>

        @if (session('error'))
            <div class="atlas-records-alerts">
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Change rejected</strong>
                    <span class="text-fg-muted">{{ session('error') }}</span>
                </div>
            </div>
        @endif

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

        <form method="POST" action="{{ route('admin.courses.missions.checks.update', [$course, $mission, $check]) }}">
            @csrf
            @method('PUT')

            <section class="atlas-record-group" aria-labelledby="check-metadata-title">
                <div class="atlas-record-group-head">
                    <h2 id="check-metadata-title" class="atlas-record-group-title">Check metadata</h2>
                </div>

                <div class="atlas-form">
                    <div class="atlas-form-grid">
                        <div class="atlas-form-field atlas-form-field--wide">
                            <label for="title" class="atlas-lesson-entry-label">Title</label>
                            <input
                                id="title"
                                type="text"
                                name="title"
                                value="{{ old('title', $check->title) }}"
                                required
                                maxlength="128"
                                class="terminal-input"
                             @if ($errors->has('title')) aria-invalid="true" aria-describedby="title-error" @endif>
                            @error('title')<p id="title-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="atlas-form-field">
                            <label for="status" class="atlas-lesson-entry-label">Status</label>
                            <select id="status" name="status" class="terminal-input" aria-describedby="{{ $errors->has('status') ? 'status-help status-error' : 'status-help' }}" @if ($errors->has('status')) aria-invalid="true" @endif>
                                @foreach (['draft', 'published', 'archived'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $check->status) === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            @error('status')<p id="status-error" class="atlas-field-error">{{ $message }}</p>@enderror
                            <p id="status-help" class="atlas-field-help">Only published checks can gate work. Status-only changes never bump the content version.</p>
                        </div>

                        <div class="atlas-form-field">
                            <label for="is_required" class="atlas-lesson-entry-label">Required</label>
                            <input type="hidden" name="is_required" value="0">
                            <label class="flex items-center gap-2 text-sm text-fg-muted">
                                <input id="is_required" type="checkbox" name="is_required" value="1" @checked(old('is_required', $check->is_required))>
                                Require a submitted attempt before challenge work
                            </label>
                            @error('is_required')<p id="is_required-error" class="atlas-field-error">{{ $message }}</p>@enderror
                            <p class="atlas-field-help">A submitted attempt clears the gate whether it passes or fails. Already-completed challenges are never re-locked.</p>
                        </div>

                        <div class="atlas-form-field atlas-form-field--wide">
                            <label for="instructions" class="atlas-lesson-entry-label">Instructions</label>
                            <textarea
                                id="instructions"
                                name="instructions"
                                rows="6"
                                class="terminal-input"
                             @if ($errors->has('instructions')) aria-invalid="true" aria-describedby="instructions-error" @endif>{{ old('instructions', $check->instructions ?? '') }}</textarea>
                            @error('instructions')<p id="instructions-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>

            <div class="atlas-form">
                <div class="atlas-form-actions">
                    <button type="submit" class="btn btn-primary">Save Knowledge Check →</button>
                    <a href="{{ route('admin.courses.missions.checks', [$course, $mission]) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </form>
    </div>
@endsection
