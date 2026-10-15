@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$assessment->title)

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>Edit Boss Challenge</h1><p>{{ $course->name }} → {{ $assessment->title }} — assessment fields. Locking or drafting seals the challenge without rewriting recorded attempts. Grading rules are view-only.</p></div>
        <div class="admin-actions"><a href="{{ route('admin.courses.assessment', $course) }}" class="btn-ghost">◀ CANCEL</a></div>
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

    <form method="POST" action="{{ route('admin.courses.assessment.update', [$course, $assessment]) }}" class="admin-form">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label for="title" class="block text-sm font-bold text-fg-muted mb-1">
                    Title
                </label>
                <input
                    id="title"
                    type="text"
                    name="title"
                    value="{{ old('title', $assessment->title) }}"
                    required
                    maxlength="128"
                    class="terminal-input"
                 @if ($errors->has('title')) aria-invalid="true" aria-describedby="title-error" @endif>
                @error('title')<p id="title-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-bold text-fg-muted mb-1">
                    Status
                </label>
                <select id="status" name="status" class="terminal-input" aria-describedby="{{ $errors->has('status') ? 'status-help status-error' : 'status-help' }}" @if ($errors->has('status')) aria-invalid="true" @endif>
                    @foreach (['active', 'locked', 'draft'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $assessment->status) === $status)>
                            {{ strtoupper($status) }}
                        </option>
                    @endforeach
                </select>
                @error('status')<p id="status-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="status-help" class="text-xs font-bold text-fg-muted mt-1">
                    ACCESS GATE — LOCKED/DRAFT SEALS THE CHALLENGE, NEVER REWRITES RECORDED ATTEMPTS
                </p>
            </div>

            <div>
                <label for="passing_score" class="block text-sm font-bold text-fg-muted mb-1">
                    Passing Score
                </label>
                <input
                    id="passing_score"
                    type="number"
                    name="passing_score"
                    value="{{ old('passing_score', $assessment->passing_score) }}"
                    required
                    min="0"
                    class="terminal-input"
                 aria-describedby="{{ $errors->has('passing_score') ? 'passing_score-help passing_score-error' : 'passing_score-help' }}" @if ($errors->has('passing_score')) aria-invalid="true" @endif>
                @error('passing_score')<p id="passing_score-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="passing_score-help" class="text-xs font-bold text-fg-muted mt-1">
                    AFFECTS FUTURE EVALUATIONS ONLY — RECORDED VERDICTS ARE NEVER REWRITTEN
                </p>
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-bold text-fg-muted mb-1">
                    Description
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    class="terminal-input"
                 @if ($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif>{{ old('description', $assessment->description ?? '') }}</textarea>
                @error('description')<p id="description-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label for="instructions" class="block text-sm font-bold text-fg-muted mb-1">
                    Instructions
                </label>
                <textarea
                    id="instructions"
                    name="instructions"
                    rows="6"
                    class="terminal-input font-body"
                 @if ($errors->has('instructions')) aria-invalid="true" aria-describedby="instructions-error" @endif>{{ old('instructions', $assessment->instructions ?? '') }}</textarea>
                @error('instructions')<p id="instructions-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <div class="mb-2 flex items-center gap-2">
                    <span class="text-sm font-bold text-fg-muted">GRADING RULES (VIEW-ONLY)</span>
                    <x-badge tone="dim">VIEW ONLY</x-badge>
                </div>
                <pre class="terminal-input whitespace-pre-wrap break-words p-3 text-[14px] text-ink">{{ $assessment->grading_rule ?? '(none)' }}</pre>
            </div>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn-primary">SAVE CHALLENGE →</button>
            <a href="{{ route('admin.courses.assessment', $course) }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
    </div>
@endsection
