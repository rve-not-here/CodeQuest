@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$section->title)

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>Edit Section</h1><p>{{ $course->name }} · {{ $section->title }}</p></div>
        <div class="admin-actions"><a href="{{ route('admin.courses.sections', $course) }}" class="btn-ghost">◀ CANCEL</a></div>
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

    <form method="POST" action="{{ route('admin.courses.sections.update', [$course, $section]) }}" class="admin-form">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label for="title" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Title
                </label>
                <input
                    id="title"
                    type="text"
                    name="title"
                    value="{{ old('title', $section->title) }}"
                    required
                    maxlength="128"
                    class="terminal-input"
                 @if ($errors->has('title')) aria-invalid="true" aria-describedby="title-error" @endif>
                @error('title')<p id="title-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="order_num" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Order
                </label>
                <input
                    id="order_num"
                    type="number"
                    name="order_num"
                    value="{{ old('order_num', $section->order_num) }}"
                    required
                    min="0"
                    class="terminal-input"
                 aria-describedby="{{ $errors->has('order_num') ? 'order_num-help order_num-error' : 'order_num-help' }}" @if ($errors->has('order_num')) aria-invalid="true" @endif>
                @error('order_num')<p id="order_num-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="order_num-help" class="text-xs font-bold text-phosphor-dim mt-1">
                    THE SINGLE ORDERING KEY — LOWER RENDERS FIRST
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
                 @if ($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif>{{ old('description', $section->description ?? '') }}</textarea>
                @error('description')<p id="description-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn-primary">SAVE SECTION →</button>
            <a href="{{ route('admin.courses.sections', $course) }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
    </div>
@endsection
