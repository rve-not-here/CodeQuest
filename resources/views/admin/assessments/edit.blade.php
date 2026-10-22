@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$assessment->title)

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · {{ $course->name }}</span>
            <a href="{{ route('admin.courses') }}">All courses →</a>
        </div>

    @include('admin.courses._nav', ['course' => $course, 'active' => 'assessment'])

        <h1 class="sr-only">Edit Boss Challenge</h1>

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

        <form method="POST" action="{{ route('admin.courses.assessment.update', [$course, $assessment]) }}">
            @csrf
            @method('PUT')

            <section class="atlas-record-group" aria-labelledby="challenge-details-title">
                <div class="atlas-record-group-head">
                    <h2 id="challenge-details-title" class="atlas-record-group-title">Challenge details</h2>
                </div>

                <div class="atlas-form">
                    <div class="atlas-form-grid">
                        <div class="atlas-form-field atlas-form-field--wide">
                            <label for="title" class="atlas-lesson-entry-label">Title</label>
                            <input
                                id="title"
                                type="text"
                                name="title"
                                value="{{ old('title', $assessment->title) }}"
                                required
                                maxlength="128"
                                class="terminal-input"
                             @if ($errors->has('title')) aria-invalid="true" aria-describedby="title-error" @endif>
                            @error('title')<p id="title-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="atlas-form-field">
                            <label for="status" class="atlas-lesson-entry-label">Status</label>
                            <select id="status" name="status" class="terminal-input" aria-describedby="{{ $errors->has('status') ? 'status-help status-error' : 'status-help' }}" @if ($errors->has('status')) aria-invalid="true" @endif>
                                @foreach (['active', 'locked', 'draft'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $assessment->status) === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            @error('status')<p id="status-error" class="atlas-field-error">{{ $message }}</p>@enderror
                            <p id="status-help" class="atlas-field-help">Access gate — locked or draft seals the challenge, never rewrites recorded attempts.</p>
                        </div>

                        <div class="atlas-form-field">
                            <label for="passing_score" class="atlas-lesson-entry-label">Passing score</label>
                            <input
                                id="passing_score"
                                type="number"
                                name="passing_score"
                                value="{{ old('passing_score', $assessment->passing_score) }}"
                                required
                                min="1"
                                max="100"
                                class="terminal-input"
                             aria-describedby="{{ $errors->has('passing_score') ? 'passing_score-help passing_score-error' : 'passing_score-help' }}" @if ($errors->has('passing_score')) aria-invalid="true" @endif>
                            @error('passing_score')<p id="passing_score-error" class="atlas-field-error">{{ $message }}</p>@enderror
                            <p id="passing_score-help" class="atlas-field-help">1–100. Affects future evaluations only — recorded verdicts are never rewritten.</p>
                        </div>

                        <div class="atlas-form-field atlas-form-field--wide">
                            <label for="description" class="atlas-lesson-entry-label">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                class="terminal-input"
                             @if ($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif>{{ old('description', $assessment->description ?? '') }}</textarea>
                            @error('description')<p id="description-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="atlas-form-field atlas-form-field--wide">
                            <label for="instructions" class="atlas-lesson-entry-label">Instructions</label>
                            <textarea
                                id="instructions"
                                name="instructions"
                                rows="6"
                                class="terminal-input font-body"
                             @if ($errors->has('instructions')) aria-invalid="true" aria-describedby="instructions-error" @endif>{{ old('instructions', $assessment->instructions ?? '') }}</textarea>
                            @error('instructions')<p id="instructions-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="atlas-record-group" aria-labelledby="grading-rules-title">
                <div class="atlas-record-group-head">
                    <h2 id="grading-rules-title" class="atlas-record-group-title">Grading rules</h2>
                    <span class="atlas-stamp atlas-stamp--ink"><x-icon name="pencil" size="12" />Editable</span>
                </div>

                <div class="atlas-form">
                    <div class="atlas-form-grid">
                        <div class="atlas-form-field atlas-form-field--wide">
                            <label for="grading_rule" class="atlas-lesson-entry-label">Rule definition (JSON)</label>
                            <textarea
                                id="grading_rule"
                                name="grading_rule"
                                rows="12"
                                class="terminal-input font-mono"
                                spellcheck="false"
                             aria-describedby="{{ $errors->has('grading_rule') ? 'grading_rule-help grading_rule-error' : 'grading_rule-help' }}" @if ($errors->has('grading_rule')) aria-invalid="true" @endif>{{ old('grading_rule', $assessment->grading_rule ?? '') }}</textarea>
                            @error('grading_rule')<p id="grading_rule-error" class="atlas-field-error">{{ $message }}</p>@enderror
                            <p id="grading_rule-help" class="atlas-field-help">A non-empty JSON array of supported pattern rules. Changes bump the assessment version; recorded verdicts keep their original rule snapshot. Hidden behavioural tests are managed separately.</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="atlas-form">
                <div class="atlas-form-actions">
                    <button type="submit" class="btn btn-primary">Save challenge →</button>
                    <a href="{{ route('admin.courses.assessment', $course) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </form>
    </div>
@endsection
