@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$mission->title)

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Challenges · {{ $course->name }}</span>
            <div class="atlas-records-running-links">
                <a href="{{ route('admin.courses.missions', $course) }}">← Challenges</a>
                <a href="{{ route('admin.courses.edit', $course) }}">{{ $course->name }} →</a>
            </div>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Curriculum</span>
            <h1 class="atlas-records-title">Edit challenge</h1>
            <p class="atlas-records-lede">{{ $course->name }} · {{ $mission->title }}</p>
            <dl class="log-facts atlas-records-facts" aria-label="Challenge record">
                <div><dt>Order</dt><dd class="is-numeric">{{ $mission->order_num }}</dd></div>
                <div><dt>Difficulty</dt><dd>{{ ucfirst(strtolower($mission->difficulty)) }}</dd></div>
                <div><dt>Points</dt><dd class="is-numeric">+{{ $mission->points }} XP</dd></div>
                <div><dt>Section</dt><dd>{{ $mission->section?->title ?? 'None' }}</dd></div>
            </dl>
        </header>

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

        <form method="POST" action="{{ route('admin.courses.missions.update', [$course, $mission]) }}">
            @csrf
            @method('PUT')

            <section class="atlas-record-group" aria-labelledby="challenge-record-title">
                <div class="atlas-record-group-head">
                    <h2 id="challenge-record-title" class="atlas-record-group-title">Challenge</h2>
                    <p class="atlas-record-group-figure">Placement and reward</p>
                </div>

                <div class="atlas-form">
                    <div class="atlas-form-grid">
                        <div class="atlas-form-field atlas-form-field--wide">
                            <label for="title" class="atlas-lesson-entry-label">Title</label>
                            <input
                                id="title"
                                type="text"
                                name="title"
                                value="{{ old('title', $mission->title) }}"
                                required
                                maxlength="128"
                                class="terminal-input"
                             @if ($errors->has('title')) aria-invalid="true" aria-describedby="title-error" @endif>
                            @error('title')<p id="title-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="atlas-form-field">
                            <label for="difficulty" class="atlas-lesson-entry-label">Difficulty</label>
                            <select id="difficulty" name="difficulty" class="terminal-input" @if ($errors->has('difficulty')) aria-invalid="true" aria-describedby="difficulty-error" @endif>
                                @foreach (['EASY', 'MEDIUM', 'HARD'] as $difficulty)
                                    <option value="{{ $difficulty }}" @selected(old('difficulty', $mission->difficulty) === $difficulty)>{{ ucfirst(strtolower($difficulty)) }}</option>
                                @endforeach
                            </select>
                            @error('difficulty')<p id="difficulty-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="atlas-form-field">
                            <label for="points" class="atlas-lesson-entry-label">Points</label>
                            <input
                                id="points"
                                type="number"
                                name="points"
                                value="{{ old('points', $mission->points) }}"
                                required
                                min="0"
                                class="terminal-input"
                             aria-describedby="{{ $errors->has('points') ? 'points-help points-error' : 'points-help' }}" @if ($errors->has('points')) aria-invalid="true" @endif>
                            @error('points')<p id="points-error" class="atlas-field-error">{{ $message }}</p>@enderror
                            <p id="points-help" class="atlas-field-help">Affects future completions only — recorded XP is never rewritten.</p>
                        </div>

                        <div class="atlas-form-field">
                            <label for="order_num" class="atlas-lesson-entry-label">Order</label>
                            <input
                                id="order_num"
                                type="number"
                                name="order_num"
                                value="{{ old('order_num', $mission->order_num) }}"
                                required
                                min="0"
                                class="terminal-input"
                             aria-describedby="{{ $errors->has('order_num') ? 'order_num-help order_num-error' : 'order_num-help' }}" @if ($errors->has('order_num')) aria-invalid="true" @endif>
                            @error('order_num')<p id="order_num-error" class="atlas-field-error">{{ $message }}</p>@enderror
                            <p id="order_num-help" class="atlas-field-help">The single ordering key — lower renders first.</p>
                        </div>

                        <div class="atlas-form-field">
                            <label for="section_id" class="atlas-lesson-entry-label">Section</label>
                            <select id="section_id" name="section_id" class="terminal-input" aria-describedby="{{ $errors->has('section_id') ? 'section_id-help section_id-error' : 'section_id-help' }}" @if ($errors->has('section_id')) aria-invalid="true" @endif>
                                <option value="" @selected(old('section_id', $mission->section_id) === null)>— No section —</option>
                                @foreach ($sections as $section)
                                    <option
                                        value="{{ $section->id }}"
                                        @selected((string) old('section_id', $mission->section_id) === (string) $section->id)
                                    >{{ $section->title }}</option>
                                @endforeach
                            </select>
                            @error('section_id')<p id="section_id-error" class="atlas-field-error">{{ $message }}</p>@enderror
                            <p id="section_id-help" class="atlas-field-help">May only be a section of this course.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="atlas-record-group" aria-labelledby="briefing-title">
                <div class="atlas-record-group-head">
                    <h2 id="briefing-title" class="atlas-record-group-title">Briefing</h2>
                    <p class="atlas-record-group-figure">What the student reads</p>
                </div>

                <div class="atlas-form">
                    <div class="atlas-form-grid">
                        <div class="atlas-form-field atlas-form-field--wide">
                            <label for="description" class="atlas-lesson-entry-label">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                class="terminal-input"
                             @if ($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif>{{ old('description', $mission->description ?? '') }}</textarea>
                            @error('description')<p id="description-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="atlas-form-field atlas-form-field--wide">
                            <label for="hints" class="atlas-lesson-entry-label">Hints</label>
                            <textarea
                                id="hints"
                                name="hints"
                                rows="4"
                                class="terminal-input"
                             @if ($errors->has('hints')) aria-invalid="true" aria-describedby="hints-error" @endif>{{ old('hints', $mission->hints ?? '') }}</textarea>
                            @error('hints')<p id="hints-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="atlas-record-group" aria-labelledby="code-title">
                <div class="atlas-record-group-head">
                    <h2 id="code-title" class="atlas-record-group-title">Code</h2>
                    <p class="atlas-record-group-figure">Workspace source and validation</p>
                </div>

                <div class="atlas-form">
                    <div class="atlas-form-grid">
                        <div class="atlas-form-field atlas-form-field--wide">
                            <label for="broken_code" class="atlas-lesson-entry-label">Broken code</label>
                            <textarea
                                id="broken_code"
                                name="broken_code"
                                rows="6"
                                class="terminal-input font-body"
                             @if ($errors->has('broken_code')) aria-invalid="true" aria-describedby="broken_code-error" @endif>{{ old('broken_code', $mission->broken_code ?? '') }}</textarea>
                            @error('broken_code')<p id="broken_code-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="atlas-form-field atlas-form-field--wide">
                            <label for="target_html" class="atlas-lesson-entry-label">Target HTML</label>
                            <textarea
                                id="target_html"
                                name="target_html"
                                rows="6"
                                class="terminal-input font-body"
                             @if ($errors->has('target_html')) aria-invalid="true" aria-describedby="target_html-error" @endif>{{ old('target_html', $mission->target_html ?? '') }}</textarea>
                            @error('target_html')<p id="target_html-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="atlas-form-field atlas-form-field--wide">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="atlas-lesson-entry-label">Solution code</span>
                                <span class="atlas-stamp atlas-stamp--ink"><x-icon name="eye" size="12" />View only</span>
                            </div>
                            <pre class="code-block whitespace-pre-wrap break-words">{{ $mission->solution_code ?? '(none)' }}</pre>
                        </div>

                        <div class="atlas-form-field atlas-form-field--wide">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="atlas-lesson-entry-label">Validation rules</span>
                                <span class="atlas-stamp atlas-stamp--ink"><x-icon name="eye" size="12" />View only</span>
                            </div>
                            <pre class="code-block whitespace-pre-wrap break-words">{{ $mission->validate_rule ?? '(none)' }}</pre>
                        </div>
                    </div>
                </div>
            </section>

            <div class="atlas-form">
                <div class="atlas-form-actions">
                    <button type="submit" class="btn btn-primary">Save challenge →</button>
                    <a href="{{ route('admin.courses.missions', $course) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </form>
    </div>
@endsection
