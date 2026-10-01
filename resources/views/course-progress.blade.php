@extends('layouts.app', ['role' => $role])

@section('title', 'Course Progress')

@section('content')
    <x-page-header
        title="Course Progress"
        subtitle="Fleet status. Mission and challenge state per course directive."
    >
        <x-slot:actions>
            <x-badge tone="phosphor">XP {{ $totalXp }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if ($overview->isEmpty())
        <x-status-message type="info" title="NO COURSES">
            No course directives are loaded. Awaiting new directives from Command.
        </x-status-message>
    @endif

    <div class="space-y-4">
        @foreach ($overview as $row)
            @php
                $course = $row['course'];
                $state = $row['state'];
                $stateTone = match ($state) {
                    'COMPLETED' => 'phosphor',
                    'READY' => 'amber',
                    'IN PROGRESS' => 'cyan',
                    'LOCKED' => 'dim',
                    default => 'dim',
                };
                $xYTone = $row['progress']['percent'] >= 100 ? 'phosphor' : ($course->status === 'locked' ? 'dim' : 'cyan');
            @endphp

            <x-panel title="{{ $course->name }}">
                <x-slot:actions>
                    <x-badge tone="{{ $xYTone }}">
                        {{ $row['progress']['completed'] }}/{{ $row['progress']['total'] }}
                    </x-badge>
                    <x-badge tone="{{ $stateTone }}">{{ $state }}</x-badge>
                </x-slot:actions>

                @if ($course->description)
                    <p class="font-body text-[15px] text-ink mb-4">{{ $course->description }}</p>
                @endif

                <x-progress-bar
                    label="Course Progress"
                    :total="$row['progress']['total']"
                    :current="$row['progress']['completed']"
                    tone="{{ $state === 'COMPLETED' ? 'phosphor' : ($state === 'LOCKED' ? 'dim' : 'amber') }}"
                />
            </x-panel>
        @endforeach
    </div>
@endsection