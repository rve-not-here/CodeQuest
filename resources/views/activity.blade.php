@extends('layouts.app', ['role' => $role])

@section('title', 'Activity')

@section('content')
    <x-page-header
        title="Learning Activity"
        subtitle="Meaningful learning events across the fleet. Server-side filter, same event vocabulary as the student timeline."
    >
        <x-slot:actions>
            <x-badge tone="cyan">SYSTEM-WIDE</x-badge>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('activity') }}" class="panel p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label for="student" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Student
                </label>
                <select id="student" name="student" class="terminal-input">
                    <option value="">ALL STUDENTS</option>
                    @foreach ($filterStudents as $student)
                        <option
                            value="{{ $student->id }}"
                            @selected(($filters['student'] ?? null) == $student->id)
                        >{{ $student->username }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="course" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Course
                </label>
                <select id="course" name="course" class="terminal-input">
                    <option value="">ALL COURSES</option>
                    @foreach ($filterCourses as $course)
                        <option
                            value="{{ $course->id }}"
                            @selected(($filters['course'] ?? null) == $course->id)
                        >{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="type" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Event type
                </label>
                <select id="type" name="type" class="terminal-input">
                    <option value="">ALL TYPES</option>
                    @foreach ($filterTypes as $type)
                        <option
                            value="{{ $type }}"
                            @selected(($filters['type'] ?? null) === $type)
                        >{{ strtoupper(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="from" class="block text-sm font-bold text-phosphor-dim mb-1">
                        From
                    </label>
                    <input id="from" name="from" type="date" value="{{ $filters['from'] }}" class="terminal-input">
                </div>
                <div>
                    <label for="to" class="block text-sm font-bold text-phosphor-dim mb-1">
                        To
                    </label>
                    <input id="to" name="to" type="date" value="{{ $filters['to'] }}" class="terminal-input">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn-ghost">FILTER →</button>
                @if (! empty($filters['student']) || ! empty($filters['course']) || ! empty($filters['type']) || ($filters['from'] ?? '') !== '' || ($filters['to'] ?? '') !== '')
                    <a href="{{ route('activity') }}" class="btn-ghost">CLEAR</a>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <x-status-message type="error" title="QUERY REJECTED" class="mt-3">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </x-status-message>
        @endif
    </form>

    <x-panel title="EVENT LOG">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <p class="text-sm font-bold text-phosphor-dim">
                WINDOW {{ $filters['from'] }} → {{ $filters['to'] }}
            </p>
            <x-badge tone="dim">{{ $events->total() }} EVENTS</x-badge>
        </div>

        @if ($events->isEmpty())
            <x-status-message type="info" title="NO ACTIVITY">
                No learning events match the current scan parameters.
            </x-status-message>
        @else
            <div class="divide-y divide-phosphor-dim/40">
                @foreach ($events as $event)
                    @php
                        [$icon, $tone] = match ($event['type']) {
                            'mission_completed' => ['⚡', 'text-phosphor'],
                            'wrong_submission' => ['✕', 'text-alert'],
                            'knowledge_check_completed' => ['?', 'text-cyan'],
                            'hint_used' => ['◈', 'text-cyan'],
                            'solution_revealed' => ['◎', 'text-amber'],
                            'assessment_completed', 'assessment_passed' => ['◆', 'text-phosphor'],
                            'assessment_failed' => ['◈', 'text-alert'],
                            'section_completed' => ['▦', 'text-amber'],
                            default => ['·', 'text-phosphor-dim'],
                        };
                    @endphp
                    <div class="flex items-center gap-3 py-2">
                        <span class="font-display text-[14px] leading-none {{ $tone }} w-4 shrink-0 text-center">{{ $icon }}</span>
                        <a
                            href="{{ route('student-progress', ['student' => $event['user']['id']]) }}"
                            class="text-sm font-bold text-phosphor hover:underline shrink-0"
                        >{{ $event['user']['username'] }}</a>
                        <span class="font-body text-[15px] text-ink truncate min-w-0">{{ $event['label'] }}</span>
                        @if ($event['pts'] !== null && $event['pts'] !== 0)
                            <x-badge tone="{{ $event['pts'] > 0 ? 'phosphor' : 'alert' }}">
                                {{ $event['pts'] > 0 ? '+' : '' }}{{ $event['pts'] }} XP
                            </x-badge>
                        @endif
                        <span class="text-xs font-bold text-phosphor-dim shrink-0 ml-auto">
                            {{ $event['at']->format('M d, H:i') }}
                        </span>
                    </div>
                @endforeach
            </div>

            @if ($events->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-4 gap-y-2 p-3 border-t border-phosphor-dim/60">
                    <p class="text-sm font-bold text-phosphor-dim">
                        SHOWING PAGE {{ $events->currentPage() }} OF {{ $events->lastPage() }}
                    </p>
                    <div class="flex items-center gap-2">
                        @if ($events->onFirstPage())
                            <span class="btn-ghost opacity-50 pointer-events-none">◀ PREV</span>
                        @else
                            <a href="{{ $events->previousPageUrl() }}" class="btn-ghost">◀ PREV</a>
                        @endif

                        @if ($events->hasMorePages())
                            <a href="{{ $events->nextPageUrl() }}" class="btn-ghost">NEXT ▶</a>
                        @else
                            <span class="btn-ghost opacity-50 pointer-events-none">NEXT ▶</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </x-panel>
@endsection
