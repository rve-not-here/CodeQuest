@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$course->name)

@section('content')
    <x-page-header
        title="Edit Course"
        subtitle="{{ $course->name }} · Update course details and availability."
        icon="▤"
    >
        <x-slot:actions>
            <a href="{{ route('admin.courses') }}" class="btn-ghost">◀ CANCEL</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('error'))
        <x-status-message type="error" title="CHANGE REJECTED" class="mb-4">
            {{ session('error') }}
        </x-status-message>
    @endif

    @if ($errors->any())
        <x-status-message type="error" title="INPUT REJECTED" class="mb-4">
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </x-status-message>
    @endif

    <form method="POST" action="{{ route('admin.courses.update', $course) }}" class="panel p-4 max-w-2xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-bold text-phosphor-dim mb-1">
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
                >
            </div>

            <div>
                <label for="slug" class="block text-sm font-bold text-phosphor-dim mb-1">
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
                >
                <p class="text-xs font-bold text-phosphor-dim mt-1">
                    KEBAB-CASE, UNIQUE ACROSS COURSES
                </p>
            </div>

            <div>
                <label for="type" class="block text-sm font-bold text-phosphor-dim mb-1">
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
                >
            </div>

            <div>
                <label for="order_num" class="block text-sm font-bold text-phosphor-dim mb-1">
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
                >
                <p class="text-xs font-bold text-phosphor-dim mt-1">
                    THE SINGLE ORDERING KEY — LOWER RENDERS FIRST
                </p>
            </div>

            <div>
                <label for="status" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Status
                </label>
                <select id="status" name="status" class="terminal-input">
                    @foreach (['active', 'locked', 'draft'] as $candidate)
                        <option value="{{ $candidate }}" @selected(old('status', $course->status) === $candidate)>
                            {{ strtoupper($candidate) }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs font-bold text-phosphor-dim mt-1">
                    CATALOG-LEVEL — NEVER REWRITES STUDENT PROGRESS
                </p>
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Description
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    class="terminal-input"
                >{{ old('description', $course->description ?? '') }}</textarea>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="btn-primary">SAVE COURSE →</button>
            <a href="{{ route('admin.courses') }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
@endsection