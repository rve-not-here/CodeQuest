@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$course->name)

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Courses · Platform scope</span>
            <div class="atlas-records-running-links">
                <a href="{{ route('admin.courses.sections', $course) }}">Sections →</a>
                <a href="{{ route('admin.courses.missions', $course) }}">Challenges →</a>
                <a href="{{ route('admin.courses') }}">All courses →</a>
            </div>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Curriculum</span>
            <h1 class="atlas-records-title">Edit course</h1>
            <p class="atlas-records-lede">{{ $course->name }} · Update course details and availability. Recorded progress is never rewritten.</p>
            <dl class="log-facts atlas-records-facts" aria-label="Course record">
                <div><dt>Order</dt><dd class="is-numeric">{{ $course->order_num }}</dd></div>
                <div><dt>Type</dt><dd>{{ $course->type }}</dd></div>
                <div><dt>Status</dt><dd>{{ ucfirst($course->status) }}</dd></div>
                <div><dt>Sections</dt><dd class="is-numeric">{{ $course->sections_count }}</dd></div>
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

        <section class="atlas-record-group" aria-labelledby="course-details-title">
            <div class="atlas-record-group-head">
                <h2 id="course-details-title" class="atlas-record-group-title">Course details</h2>
            </div>

            <form method="POST" action="{{ route('admin.courses.update', $course) }}" class="atlas-form">
                @csrf
                @method('PUT')

                <div class="atlas-form-grid">
                    <div class="atlas-form-field">
                        <label for="name" class="atlas-lesson-entry-label">Name</label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $course->name) }}"
                            required
                            maxlength="128"
                            class="terminal-input"
                         @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                        @error('name')<p id="name-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="slug" class="atlas-lesson-entry-label">Slug</label>
                        <input
                            id="slug"
                            type="text"
                            name="slug"
                            value="{{ old('slug', $course->slug) }}"
                            required
                            maxlength="64"
                            pattern="[a-z0-9-]+"
                            class="terminal-input"
                         aria-describedby="{{ $errors->has('slug') ? 'slug-help slug-error' : 'slug-help' }}" @if ($errors->has('slug')) aria-invalid="true" @endif>
                        @error('slug')<p id="slug-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        <p id="slug-help" class="atlas-field-help">Kebab-case, unique across courses.</p>
                    </div>

                    <div class="atlas-form-field">
                        <label for="type" class="atlas-lesson-entry-label">Type</label>
                        <input
                            id="type"
                            type="text"
                            name="type"
                            value="{{ old('type', $course->type) }}"
                            required
                            maxlength="16"
                            class="terminal-input"
                         @if ($errors->has('type')) aria-invalid="true" aria-describedby="type-error" @endif>
                        @error('type')<p id="type-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="order_num" class="atlas-lesson-entry-label">Order</label>
                        <input
                            id="order_num"
                            type="number"
                            name="order_num"
                            value="{{ old('order_num', $course->order_num) }}"
                            required
                            min="0"
                            class="terminal-input"
                         aria-describedby="{{ $errors->has('order_num') ? 'order_num-help order_num-error' : 'order_num-help' }}" @if ($errors->has('order_num')) aria-invalid="true" @endif>
                        @error('order_num')<p id="order_num-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        <p id="order_num-help" class="atlas-field-help">The single ordering key — lower renders first.</p>
                    </div>

                    <div class="atlas-form-field">
                        <label for="status" class="atlas-lesson-entry-label">Status</label>
                        <select id="status" name="status" class="terminal-input" aria-describedby="{{ $errors->has('status') ? 'status-help status-error' : 'status-help' }}" @if ($errors->has('status')) aria-invalid="true" @endif>
                            @foreach (['active', 'locked', 'draft'] as $candidate)
                                <option value="{{ $candidate }}" @selected(old('status', $course->status) === $candidate)>
                                    {{ ucfirst($candidate) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')<p id="status-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        <p id="status-help" class="atlas-field-help">Catalog-level — never rewrites student progress.</p>
                    </div>

                    <div class="atlas-form-field atlas-form-field--wide">
                        <label for="description" class="atlas-lesson-entry-label">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="terminal-input"
                         @if ($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif>{{ old('description', $course->description ?? '') }}</textarea>
                        @error('description')<p id="description-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="atlas-form-actions">
                    <button type="submit" class="btn btn-primary">Save course →</button>
                    <a href="{{ route('admin.courses') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
