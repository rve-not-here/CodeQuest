@extends('layouts.app', ['role' => $role])

@section('title', 'New classroom')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Classrooms · Platform scope</span>
            <a href="{{ route('admin.classrooms') }}">← All classrooms</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Operations</span>
            <h1 class="atlas-records-title">New classroom</h1>
            <p class="atlas-records-lede">Create a classroom, then assign teachers, students and courses.</p>
        </header>

        @if (session('error'))
            <div class="atlas-records-alerts">
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Write refused</strong>
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

        <section class="atlas-record-group" aria-labelledby="classroom-title">
            <div class="atlas-record-group-head">
                <h2 id="classroom-title" class="atlas-record-group-title">Classroom</h2>
            </div>

            <form method="POST" action="{{ route('admin.classrooms.store') }}" class="atlas-form">
                @csrf

                <div class="atlas-form-grid">
                    <div class="atlas-form-field">
                        <label for="name" class="atlas-lesson-entry-label">Name</label>
                        <input id="name" name="name" type="text" maxlength="128" value="{{ old('name') }}" required class="terminal-input" @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                        @error('name')<p id="name-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="code" class="atlas-lesson-entry-label">Code (optional)</label>
                        <input id="code" name="code" type="text" maxlength="32" value="{{ old('code') }}" class="terminal-input" @if ($errors->has('code')) aria-invalid="true" aria-describedby="code-error" @endif>
                        @error('code')<p id="code-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="status" class="atlas-lesson-entry-label">Status</label>
                        <select id="status" name="status" class="terminal-input" aria-describedby="{{ $errors->has('status') ? 'status-help status-error' : 'status-help' }}" @if ($errors->has('status')) aria-invalid="true" @endif>
                            <option value="active" @selected(old('status', \App\Models\Classroom::STATUS_ACTIVE) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                        </select>
                        @error('status')<p id="status-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        <p id="status-help" class="atlas-field-help">Status is a visibility boundary: only active classrooms are visible to their teachers.</p>
                    </div>
                </div>

                <div class="atlas-form-actions">
                    <button type="submit" class="btn btn-primary">Create classroom →</button>
                    <a href="{{ route('admin.classrooms') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
