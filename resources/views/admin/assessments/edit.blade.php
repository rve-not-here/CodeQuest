@extends('layouts.app', ['role' => $role])

@section('title', 'Edit '.$assessment->title)

@section('content')
    <x-page-header
        title="Edit Boss Challenge"
        subtitle="{{ $course->name }} → {{ $assessment->title }} — assessment fields. assessment.status is an access gate: locking or drafting seals the challenge but never rewrites recorded attempts. Grading rules are view-only in this story."
        icon="◈"
    >
        <x-slot:actions>
            <a href="{{ route('admin.courses.assessment', $course) }}" class="btn-ghost">◀ CANCEL</a>
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

    <form method="POST" action="{{ route('admin.courses.assessment.update', [$course, $assessment]) }}" class="panel p-4 max-w-3xl">
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
                    value="{{ old('title', $assessment->title) }}"
                    required
                    maxlength="128"
                    class="terminal-input"
                >
            </div>

            <div>
                <label for="status" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Status
                </label>
                <select id="status" name="status" class="terminal-input">
                    @foreach (['active', 'locked', 'draft'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $assessment->status) === $status)>
                            {{ strtoupper($status) }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs font-bold text-phosphor-dim mt-1">
                    ACCESS GATE — LOCKED/DRAFT SEALS THE CHALLENGE, NEVER REWRITES RECORDED ATTEMPTS
                </p>
            </div>

            <div>
                <label for="passing_score" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Passing Score
                </label>
                <input
                    id="passing_score"
                    type="number"
                    name="passing_score"
                    value="{{ old('passing_score', $assessment->passing_score) }}"
                    required
                    min="0"
                    class="terminal-input"
                >
                <p class="text-xs font-bold text-phosphor-dim mt-1">
                    AFFECTS FUTURE EVALUATIONS ONLY — RECORDED VERDICTS ARE NEVER REWRITTEN
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
                >{{ old('description', $assessment->description ?? '') }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label for="instructions" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Instructions
                </label>
                <textarea
                    id="instructions"
                    name="instructions"
                    rows="6"
                    class="terminal-input font-body"
                >{{ old('instructions', $assessment->instructions ?? '') }}</textarea>
            </div>

            <div class="md:col-span-2">
                <div class="mb-2 flex items-center gap-2">
                    <span class="text-sm font-bold text-phosphor-dim">GRADING RULES (VIEW-ONLY)</span>
                    <x-badge tone="dim">NOT EDITABLE THIS STORY</x-badge>
                </div>
                <pre class="terminal-input whitespace-pre-wrap break-words p-3 text-[14px] text-ink">{{ $assessment->grading_rule ?? '(none)' }}</pre>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="btn-primary">SAVE CHALLENGE →</button>
            <a href="{{ route('admin.courses.assessment', $course) }}" class="btn-ghost">CANCEL</a>
        </div>
    </form>
@endsection