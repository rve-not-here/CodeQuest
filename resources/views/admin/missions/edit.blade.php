@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$mission->title)

@section('content')
    <x-page-header
        title="Edit Mission"
        subtitle="{{ $course->name }} → {{ $mission->title }} — mission fields. Missions carry no status; the access gate sits on course.status. Solution code and validation rules are view-only in this story."
    >
        <x-slot:actions>
            <a href="{{ route('admin.courses.missions', $course) }}" class="btn-ghost">◀ CANCEL</a>
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

    <form method="POST" action="{{ route('admin.courses.missions.update', [$course, $mission]) }}" class="panel p-4 max-w-3xl">
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
                >
            </div>

            <div>
                <label for="difficulty" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Difficulty
                </label>
                <select id="difficulty" name="difficulty" class="terminal-input">
                    @foreach (['EASY', 'MEDIUM', 'HARD'] as $difficulty)
                        <option value="{{ $difficulty }}" @selected(old('difficulty', $mission->difficulty) === $difficulty)>
                            {{ $difficulty }}
                        </option>
                    @endforeach
                </select>
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
                >
                <p class="text-xs font-bold text-phosphor-dim mt-1">
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
                >
                <p class="text-xs font-bold text-phosphor-dim mt-1">
                    THE SINGLE ORDERING KEY — LOWER RENDERS FIRST
                </p>
            </div>

            <div>
                <label for="section_id" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Section
                </label>
                <select id="section_id" name="section_id" class="terminal-input">
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
                <p class="text-xs font-bold text-phosphor-dim mt-1">
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
                >{{ old('description', $mission->description ?? '') }}</textarea>
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
                >{{ old('broken_code', $mission->broken_code ?? '') }}</textarea>
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
                >{{ old('target_html', $mission->target_html ?? '') }}</textarea>
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
                >{{ old('hints', $mission->hints ?? '') }}</textarea>
            </div>

            <div class="md:col-span-2">
                <div class="mb-2 flex items-center gap-2">
                    <span class="text-sm font-bold text-phosphor-dim">SOLUTION CODE (VIEW-ONLY)</span>
                    <x-badge tone="dim">NOT EDITABLE THIS STORY</x-badge>
                </div>
                <pre class="terminal-input whitespace-pre-wrap break-words p-3 text-[14px] text-ink">{{ $mission->solution_code ?? '(none)' }}</pre>
            </div>

            <div class="md:col-span-2">
                <div class="mb-2 flex items-center gap-2">
                    <span class="text-sm font-bold text-phosphor-dim">VALIDATION RULES (VIEW-ONLY)</span>
                    <x-badge tone="dim">NOT EDITABLE THIS STORY</x-badge>
                </div>
                <pre class="terminal-input whitespace-pre-wrap break-words p-3 text-[14px] text-ink">{{ $mission->validate_rule ?? '(none)' }}</pre>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="btn-primary">SAVE MISSION →</button>
            <a href="{{ route('admin.courses.missions', $course) }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
@endsection