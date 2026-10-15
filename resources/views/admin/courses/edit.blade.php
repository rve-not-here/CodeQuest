@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$course->name)

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>Edit Course</h1><p>{{ $course->name }} · Update course details and availability.</p></div>
        <div class="admin-actions"><a href="{{ route('admin.courses') }}" class="btn-ghost">◀ CANCEL</a></div>
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

    <form method="POST" action="{{ route('admin.courses.update', $course) }}" class="admin-form">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-bold text-fg-muted mb-1">
                    Name
                </label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $course->name) }}"
                    required
                    maxlength="128"
                    class="terminal-input"
                 @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                @error('name')<p id="name-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="slug" class="block text-sm font-bold text-fg-muted mb-1">
                    Slug
                </label>
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
                @error('slug')<p id="slug-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="slug-help" class="text-xs font-bold text-fg-muted mt-1">
                    KEBAB-CASE, UNIQUE ACROSS COURSES
                </p>
            </div>

            <div>
                <label for="type" class="block text-sm font-bold text-fg-muted mb-1">
                    Type
                </label>
                <input
                    id="type"
                    type="text"
                    name="type"
                    value="{{ old('type', $course->type) }}"
                    required
                    maxlength="16"
                    class="terminal-input"
                 @if ($errors->has('type')) aria-invalid="true" aria-describedby="type-error" @endif>
                @error('type')<p id="type-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="order_num" class="block text-sm font-bold text-fg-muted mb-1">
                    Order
                </label>
                <input
                    id="order_num"
                    type="number"
                    name="order_num"
                    value="{{ old('order_num', $course->order_num) }}"
                    required
                    min="0"
                    class="terminal-input"
                 aria-describedby="{{ $errors->has('order_num') ? 'order_num-help order_num-error' : 'order_num-help' }}" @if ($errors->has('order_num')) aria-invalid="true" @endif>
                @error('order_num')<p id="order_num-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="order_num-help" class="text-xs font-bold text-fg-muted mt-1">
                    THE SINGLE ORDERING KEY — LOWER RENDERS FIRST
                </p>
            </div>

            <div>
                <label for="status" class="block text-sm font-bold text-fg-muted mb-1">
                    Status
                </label>
                <select id="status" name="status" class="terminal-input" aria-describedby="{{ $errors->has('status') ? 'status-help status-error' : 'status-help' }}" @if ($errors->has('status')) aria-invalid="true" @endif>
                    @foreach (['active', 'locked', 'draft'] as $candidate)
                        <option value="{{ $candidate }}" @selected(old('status', $course->status) === $candidate)>
                            {{ strtoupper($candidate) }}
                        </option>
                    @endforeach
                </select>
                @error('status')<p id="status-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="status-help" class="text-xs font-bold text-fg-muted mt-1">
                    CATALOG-LEVEL — NEVER REWRITES STUDENT PROGRESS
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
                 @if ($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif>{{ old('description', $course->description ?? '') }}</textarea>
                @error('description')<p id="description-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn-primary">SAVE COURSE →</button>
            <a href="{{ route('admin.courses') }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
    </div>
@endsection
