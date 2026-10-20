@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$section->title)

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Sections · {{ $course->name }}</span>
            <div class="atlas-records-running-links">
                <a href="{{ route('admin.courses.sections', $course) }}">← Sections</a>
                <a href="{{ route('admin.courses.edit', $course) }}">{{ $course->name }} →</a>
            </div>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Curriculum</span>
            <h1 class="atlas-records-title">Edit section</h1>
            <p class="atlas-records-lede">{{ $course->name }} · {{ $section->title }}</p>
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

        <section class="atlas-record-group" aria-labelledby="section-details-title">
            <div class="atlas-record-group-head">
                <h2 id="section-details-title" class="atlas-record-group-title">Section</h2>
            </div>

            <form method="POST" action="{{ route('admin.courses.sections.update', [$course, $section]) }}" class="atlas-form">
                @csrf
                @method('PUT')

                <div class="atlas-form-grid">
                    <div class="atlas-form-field atlas-form-field--wide">
                        <label for="title" class="atlas-lesson-entry-label">Title</label>
                        <input
                            id="title"
                            type="text"
                            name="title"
                            value="{{ old('title', $section->title) }}"
                            required
                            maxlength="128"
                            class="terminal-input"
                         @if ($errors->has('title')) aria-invalid="true" aria-describedby="title-error" @endif>
                        @error('title')<p id="title-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="order_num" class="atlas-lesson-entry-label">Order</label>
                        <input
                            id="order_num"
                            type="number"
                            name="order_num"
                            value="{{ old('order_num', $section->order_num) }}"
                            required
                            min="0"
                            class="terminal-input"
                         aria-describedby="{{ $errors->has('order_num') ? 'order_num-help order_num-error' : 'order_num-help' }}" @if ($errors->has('order_num')) aria-invalid="true" @endif>
                        @error('order_num')<p id="order_num-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        <p id="order_num-help" class="atlas-field-help">The single ordering key — lower renders first.</p>
                    </div>

                    <div class="atlas-form-field atlas-form-field--wide">
                        <label for="description" class="atlas-lesson-entry-label">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="terminal-input"
                         @if ($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif>{{ old('description', $section->description ?? '') }}</textarea>
                        @error('description')<p id="description-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="atlas-form-actions">
                    <button type="submit" class="btn btn-primary">Save section →</button>
                    <a href="{{ route('admin.courses.sections', $course) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
