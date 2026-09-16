@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$section->title)

@section('content')
    <x-page-header
        title="Edit Section"
        subtitle="{{ $course->name }} → {{ $section->title }} — section fields. Sections carry no status; the access gate sits on course.status and never changes by editing a section row."
        icon="▦"
    >
        <x-slot:actions>
            <a href="{{ route('admin.courses.sections', $course) }}" class="btn-ghost">◀ CANCEL</a>
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

    <form method="POST" action="{{ route('admin.courses.sections.update', [$course, $section]) }}" class="panel p-4 max-w-2xl">
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
                    value="{{ old('order_num', $section->order_num) }}"
                    required
                    min="0"
                    class="terminal-input"
                >
                <p class="text-xs font-bold text-phosphor-dim mt-1">
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
                >{{ old('description', $section->description ?? '') }}</textarea>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="btn-primary">SAVE SECTION →</button>
            <a href="{{ route('admin.courses.sections', $course) }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
@endsection