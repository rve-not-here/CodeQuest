@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$mission->title)

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>Edit Mission</h1><p>{{ $course->name }} · {{ $mission->title }}</p></div>
        <div class="admin-actions"><a href="{{ route('admin.courses.missions', $course) }}" class="btn-ghost">◀ CANCEL</a></div>
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

    <form method="POST" action="{{ route('admin.courses.missions.update', [$course, $mission]) }}" class="admin-form">
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
                    value="{{ old('title', $mission->title) }}"
                    required
                    maxlength="128"
                    class="terminal-input"
                 @if ($errors->has('title')) aria-invalid="true" aria-describedby="title-error" @endif>
                @error('title')<p id="title-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="difficulty" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Difficulty
                </label>
                <select id="difficulty" name="difficulty" class="terminal-input" @if ($errors->has('difficulty')) aria-invalid="true" aria-describedby="difficulty-error" @endif>
                    @foreach (['EASY', 'MEDIUM', 'HARD'] as $difficulty)
                        <option value="{{ $difficulty }}" @selected(old('difficulty', $mission->difficulty) === $difficulty)>
                            {{ $difficulty }}
                        </option>
                    @endforeach
                </select>
                @error('difficulty')<p id="difficulty-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="points" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Points
                </label>
                <input
                    id="points"
                    type="number"
                    name="points"
                    value="{{ old('points', $mission->points) }}"
                    required
                    min="0"
                    class="terminal-input"
                 aria-describedby="{{ $errors->has('points') ? 'points-help points-error' : 'points-help' }}" @if ($errors->has('points')) aria-invalid="true" @endif>
                @error('points')<p id="points-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="points-help" class="text-xs font-bold text-phosphor-dim mt-1">
                    AFFECTS FUTURE COMPLETIONS ONLY — RECORDED XP IS NEVER REWRITTEN
                </p>
            </div>

            <div>
                <label for="order_num" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Order
                </label>
                <input
                    id="order_num"
                    type="number"
                    name="order_num"
                    value="{{ old('order_num', $mission->order_num) }}"
                    required
                    min="0"
                    class="terminal-input"
                 aria-describedby="{{ $errors->has('order_num') ? 'order_num-help order_num-error' : 'order_num-help' }}" @if ($errors->has('order_num')) aria-invalid="true" @endif>
                @error('order_num')<p id="order_num-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="order_num-help" class="text-xs font-bold text-phosphor-dim mt-1">
                    THE SINGLE ORDERING KEY — LOWER RENDERS FIRST
                </p>
            </div>

            <div>
                <label for="section_id" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Section
                </label>
                <select id="section_id" name="section_id" class="terminal-input" aria-describedby="{{ $errors->has('section_id') ? 'section_id-help section_id-error' : 'section_id-help' }}" @if ($errors->has('section_id')) aria-invalid="true" @endif>
                    <option value="" @selected(old('section_id', $mission->section_id) === null)>— NO SECTION —</option>
                    @foreach ($sections as $section)
                        <option
                            value="{{ $section->id }}"
                            @selected((string) old('section_id', $mission->section_id) === (string) $section->id)
                        >
                            {{ $section->title }}
                        </option>
                    @endforeach
                </select>
                @error('section_id')<p id="section_id-error" class="admin-field-error">{{ $message }}</p>@enderror
                <p id="section_id-help" class="text-xs font-bold text-phosphor-dim mt-1">
                    MAY ONLY BE A SECTION OF THIS COURSE
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
                 @if ($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif>{{ old('description', $mission->description ?? '') }}</textarea>
                @error('description')<p id="description-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label for="broken_code" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Broken Code
                </label>
                <textarea
                    id="broken_code"
                    name="broken_code"
                    rows="6"
                    class="terminal-input font-body"
                 @if ($errors->has('broken_code')) aria-invalid="true" aria-describedby="broken_code-error" @endif>{{ old('broken_code', $mission->broken_code ?? '') }}</textarea>
                @error('broken_code')<p id="broken_code-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label for="target_html" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Target HTML
                </label>
                <textarea
                    id="target_html"
                    name="target_html"
                    rows="6"
                    class="terminal-input font-body"
                 @if ($errors->has('target_html')) aria-invalid="true" aria-describedby="target_html-error" @endif>{{ old('target_html', $mission->target_html ?? '') }}</textarea>
                @error('target_html')<p id="target_html-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label for="hints" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Hints
                </label>
                <textarea
                    id="hints"
                    name="hints"
                    rows="4"
                    class="terminal-input"
                 @if ($errors->has('hints')) aria-invalid="true" aria-describedby="hints-error" @endif>{{ old('hints', $mission->hints ?? '') }}</textarea>
                @error('hints')<p id="hints-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <div class="mb-2 flex items-center gap-2">
                    <span class="text-sm font-bold text-phosphor-dim">SOLUTION CODE (VIEW-ONLY)</span>
                    <x-badge tone="dim">VIEW ONLY</x-badge>
                </div>
                <pre class="terminal-input whitespace-pre-wrap break-words p-3 text-[14px] text-ink">{{ $mission->solution_code ?? '(none)' }}</pre>
            </div>

            <div class="md:col-span-2">
                <div class="mb-2 flex items-center gap-2">
                    <span class="text-sm font-bold text-phosphor-dim">VALIDATION RULES (VIEW-ONLY)</span>
                    <x-badge tone="dim">VIEW ONLY</x-badge>
                </div>
                <pre class="terminal-input whitespace-pre-wrap break-words p-3 text-[14px] text-ink">{{ $mission->validate_rule ?? '(none)' }}</pre>
            </div>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn-primary">SAVE MISSION →</button>
            <a href="{{ route('admin.courses.missions', $course) }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
    </div>
@endsection
