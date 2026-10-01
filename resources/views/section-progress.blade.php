@extends('layouts.app', ['role' => $role])

@section('title', 'Section Progress')

@section('content')
    <x-page-header
        title="Section Progress"
        subtitle="Directive sections. Mission completion within each section."
    >
        <x-slot:actions>
            <x-badge tone="phosphor">XP {{ $totalXp }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if ($tree->isEmpty())
        <x-status-message type="info" title="NO COURSES">
            No course directives are loaded. Awaiting new directives from Command.
        </x-status-message>
    @endif

    <div class="space-y-6">
        @foreach ($tree as $courseRow)
            @php
                $course = $courseRow['course'];
                $courseTone = $courseRow['progress']['percent'] >= 100 ? 'phosphor' : ($course->status === 'locked' ? 'dim' : 'cyan');
            @endphp

            <x-panel title="{{ $course->name }}" class="panel-link">
                <x-slot:actions>
                    <x-badge tone="{{ $courseTone }}">
                        {{ $courseRow['progress']['completed'] }}/{{ $courseRow['progress']['total'] }}
                    </x-badge>
                </x-slot:actions>

                <div class="space-y-5">
                    @foreach ($courseRow['sections'] as $sectionRow)
                        @php
                            $section = $sectionRow['section'];
                            $state = $sectionRow['state'];
                            $stateTone = match ($state) {
                                'DONE' => 'phosphor',
                                'IN PROGRESS' => 'amber',
                                default => 'dim',
                            };
                            $xYTone = $sectionRow['progress']['percent'] === 100 && $sectionRow['progress']['total'] > 0
                                ? 'phosphor'
                                : ($sectionRow['progress']['total'] === 0 ? 'dim' : 'cyan');
                        @endphp

                        <div>
                            <div class="flex items-baseline justify-between gap-3 mb-2">
                                <h3 class="text-sm font-bold text-amber">
                                    {{ $section->title }}
                                </h3>
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-badge tone="{{ $xYTone }}">
                                        {{ $sectionRow['progress']['completed'] }}/{{ $sectionRow['progress']['total'] }}
                                    </x-badge>
                                    <x-badge tone="{{ $stateTone }}">{{ $state }}</x-badge>
                                </div>
                            </div>

                            <x-progress-bar
                                label="Section Progress"
                                :total="$sectionRow['progress']['total']"
                                :current="$sectionRow['progress']['completed']"
                                tone="{{ $state === 'DONE' ? 'phosphor' : 'amber' }}"
                            />
                        </div>
                    @endforeach
                </div>
            </x-panel>
        @endforeach
    </div>
@endsection