@extends('layouts.app', ['role' => $role])

@section('title', 'Learning Path')

@section('content')
    <x-page-header
        title="Learning Path"
        subtitle="Course directives. Complete missions to restore the system."
        icon="▣"
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

    <div class="space-y-8">
        @foreach ($tree as $courseNode)
            @php $course = $courseNode['course']; @endphp
            <x-panel title="{{ $course->name }}">
                <x-slot:actions>
                    <x-badge tone="{{ $courseNode['progress']['percent'] >= 100 ? 'phosphor' : ($course->status === 'locked' ? 'dim' : 'cyan') }}">
                        {{ $courseNode['progress']['completed'] }}/{{ $courseNode['progress']['total'] }}
                    </x-badge>
                </x-slot:actions>

                @if ($course->description)
                    <p class="font-body text-[15px] text-ink mb-4">{{ $course->description }}</p>
                @endif

                <x-progress-bar
                    label="Course Progress"
                    :total="$courseNode['progress']['total']"
                    :current="$courseNode['progress']['completed']"
                    tone="{{ $course->status === 'locked' ? 'dim' : 'phosphor' }}"
                />

                <div class="mt-6 space-y-6">
                    @foreach ($courseNode['sections'] as $sectionNode)
                        @php $section = $sectionNode['section']; @endphp
                        <div>
                            <div class="flex items-baseline justify-between gap-3 mb-2">
                                <h3 class="text-sm font-bold text-amber">
                                    {{ $section->title }}
                                </h3>
                                <span class="text-xs font-bold text-phosphor-dim shrink-0">
                                    {{ $sectionNode['progress']['completed'] }}/{{ $sectionNode['progress']['total'] }}
                                </span>
                            </div>

                            <div class="border border-phosphor-dim/40 divide-y divide-phosphor-dim/20">
                                @foreach ($sectionNode['missions'] as $row)
                                    @php
                                        $mission = $row['mission'];
                                        $state = $row['state'];
                                        $isCurrent = $nextMission !== null && $nextMission->id === $mission->id;
                                    @endphp
                                    <a
                                        href="{{ route('mission.show', $mission) }}"
                                        class="flex items-center gap-3 px-3 py-2 hover:bg-surface-alt transition-colors {{ $state === 'COMPLETED' ? 'opacity-70' : '' }} {{ $isCurrent ? 'bg-surface-alt' : '' }}"
                                    >
                                        <span class="font-display text-[10px] shrink-0 {{ $state === 'COMPLETED' ? 'text-phosphor' : ($state === 'IN PROGRESS' ? 'text-amber' : 'text-phosphor-dim') }}">
                                            {{ $state === 'COMPLETED' ? '●' : ($state === 'IN PROGRESS' ? '▶' : '○') }}
                                        </span>
                                        <span class="font-body text-[15px] text-ink truncate flex-1">
                                            {{ $mission->order_num }} · {{ $mission->title }}
                                        </span>
                                        <x-badge tone="{{ $mission->difficulty === 'HARD' ? 'alert' : ($mission->difficulty === 'MEDIUM' ? 'amber' : 'cyan') }}">
                                            {{ $mission->difficulty }}
                                        </x-badge>
                                        <span class="text-xs font-bold text-phosphor-dim shrink-0">
                                            {{ $state }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>

                            <x-progress-bar
                                label="Section Progress"
                                :total="$sectionNode['progress']['total']"
                                :current="$sectionNode['progress']['completed']"
                                tone="amber"
                                class="mt-2"
                            />
                        </div>
                    @endforeach
                </div>
            </x-panel>
        @endforeach
    </div>
@endsection
