@extends('layouts.app', ['role' => $role])

@section('title', 'Challenge Index')

@section('content')
    <x-page-header
        title="Challenge Index"
        subtitle="Every active challenge in course order, tagged with your server-verified state. Open one to begin writing code from scratch."
        icon="⚡"
    >
        <x-slot:actions>
            <x-badge tone="phosphor">XP {{ $totalXp }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('missions') }}" class="panel p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label for="cq-q" class="text-xs font-bold text-phosphor-dim">Search</label>
                <input
                    id="cq-q"
                    name="q"
                    type="text"
                    value="{{ $filters['q'] }}"
                    class="terminal-input w-full mt-1"
                    placeholder="Challenge or course"
                >
            </div>
            <div>
                <label for="cq-course" class="text-xs font-bold text-phosphor-dim">Course</label>
                <select id="cq-course" name="course" class="terminal-input w-full mt-1">
                    <option value="">ALL COURSES</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected($filters['course'] === $course->id)>
                            {{ $course->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="cq-status" class="text-xs font-bold text-phosphor-dim">State</label>
                <select id="cq-status" name="status" class="terminal-input w-full mt-1">
                    <option value="">ALL STATES</option>
                    @foreach (['COMPLETED', 'IN PROGRESS', 'NOT STARTED'] as $state)
                        <option value="{{ $state }}" @selected($filters['status'] === $state)>{{ $state }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-2">
            <button type="submit" class="btn-ghost">FILTER →</button>
            @if ($filters['q'] !== '' || $filters['status'] !== null || $filters['course'] !== null)
                <a href="{{ route('missions') }}" class="btn-ghost">CLEAR</a>
            @endif
        </div>
    </form>

    @if ($rows->isEmpty())
        <x-status-message type="info" title="NO CHALLENGES">
            No challenges match the current filters.
        </x-status-message>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach ($rows as $row)
            @php
                $state = $row['state'];
                $mission = $row['mission'];
                $course = $row['course'];
                $courseSealed = $course->status !== 'active';
                $stateIcon = $state === 'COMPLETED' ? '●' : ($state === 'IN PROGRESS' ? '▶' : '○');
                $stateTone = $state === 'COMPLETED' ? 'phosphor' : ($state === 'IN PROGRESS' ? 'amber' : 'dim');
            @endphp
            @if ($courseSealed)
            <article
                class="panel p-4 opacity-80"
                aria-label="{{ $mission->title }}, locked"
            >
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-amber truncate">{{ $course->name }}</p>
                        <h2 class="font-body text-[16px] text-ink truncate mt-1">{{ $mission->title }}</h2>
                    </div>
                    <x-badge tone="dim">× LOCKED</x-badge>
                </div>
                <p class="text-xs leading-relaxed text-static">Course access is sealed. Return when Command restores this course.</p>
            </article>
            @else
            <a
                href="{{ route('mission.show', $mission) }}"
                class="panel p-4 hover:border-phosphor transition-colors {{ $state === 'COMPLETED' ? 'opacity-80' : '' }}"
            >
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-amber truncate">{{ $course->name }}</p>
                        <h2 class="font-body text-[16px] text-ink truncate mt-1">{{ $mission->title }}</h2>
                    </div>
                    <x-badge tone="{{ $stateTone }}">{{ $stateIcon }} {{ $state }}</x-badge>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge tone="cyan">{{ $course->type }}</x-badge>
                    <x-badge tone="{{ $mission->difficulty === 'HARD' ? 'alert' : ($mission->difficulty === 'MEDIUM' ? 'amber' : 'cyan') }}">
                        {{ $mission->difficulty }}
                    </x-badge>
                    <span class="text-xs font-bold text-phosphor-dim">
                        {{ $mission->points }} XP · CH {{ $mission->order_num }}
                    </span>
                </div>
            </a>
            @endif
        @endforeach
    </div>
@endsection
