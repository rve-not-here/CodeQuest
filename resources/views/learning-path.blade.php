@extends('layouts.app', ['role' => $role])

@section('title', 'Learning Path')

@section('content')
    @php
        $currentCourseId = $course?->id;
    @endphp

    <x-page-header
        title="Learning Path"
        subtitle="Follow the signal rail from foundational lessons to the final course milestone."
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

    @if ($recommendations->isNotEmpty())
        <x-panel title="RECOMMENDED NEXT ACTIONS" class="mb-10">
            <x-recommendation-cards :recommendations="$recommendations" />
        </x-panel>
    @endif

    <div class="space-y-10">
        @foreach ($tree as $courseNode)
            @php
                $course = $courseNode['course'];
                $courseLocked = $course->status !== 'active';
                $isCurrentCourse = $currentCourseId === $course->id && $course->status === 'active';
                $boss = $courseNode['boss'];
            @endphp

            <article class="learning-path-course" aria-labelledby="course-{{ $course->id }}-title">
                <header class="learning-path-course-header">
                    <div class="min-w-0">
                        <p class="terminal-kicker">
                            COURSE {{ str_pad((string) $course->order_num, 2, '0', STR_PAD_LEFT) }}
                            // {{ $courseLocked ? 'SEALED' : ($isCurrentCourse ? 'CURRENT SIGNAL' : 'AVAILABLE') }}
                        </p>
                        <h2 id="course-{{ $course->id }}-title" class="mt-2 text-xl font-bold uppercase text-ink md:text-2xl">
                            {{ $course->name }}
                        </h2>
                        @if ($course->description)
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-static">{{ $course->description }}</p>
                        @endif
                    </div>
                    <div class="learning-path-course-progress">
                        <strong>{{ $courseNode['progress']['percent'] }}%</strong>
                        <span>{{ $courseNode['progress']['completed'] }}/{{ $courseNode['progress']['total'] }} COMPLETE</span>
                    </div>
                </header>

                <div class="border-t border-phosphor/15 p-4 sm:p-6">
                    <x-progress-bar
                        label="Course Progress"
                        :total="$courseNode['progress']['total']"
                        :current="$courseNode['progress']['completed']"
                        tone="{{ $courseLocked ? 'dim' : 'phosphor' }}"
                    />

                    <div class="learning-path-rail">
                        @foreach ($courseNode['sections'] as $sectionNode)
                            @php
                                $section = $sectionNode['section'];
                            @endphp
                            <section class="path-section" aria-labelledby="section-{{ $section->id }}-title">
                                <header class="path-section-header">
                                    <div>
                                        <p class="terminal-kicker text-amber">SECTION {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                                        <h3 id="section-{{ $section->id }}-title">{{ $section->title }}</h3>
                                    </div>
                                    <span>{{ $sectionNode['progress']['completed'] }}/{{ $sectionNode['progress']['total'] }}</span>
                                </header>

                                <ol class="path-node-list">
                                    @foreach ($sectionNode['missions'] as $row)
                                        @php
                                            $mission = $row['mission'];
                                            $isCurrent = ! $courseLocked && $nextMission !== null && $nextMission->id === $mission->id;
                                            $state = $courseLocked
                                                ? 'LOCKED'
                                                : ($row['state'] === 'COMPLETED' ? 'COMPLETED' : ($isCurrent ? 'CURRENT' : 'AVAILABLE'));
                                            $stateClass = strtolower($state);
                                            $marker = match ($state) {
                                                'COMPLETED' => '✓',
                                                'CURRENT' => '●',
                                                'AVAILABLE' => '○',
                                                default => '×',
                                            };
                                        @endphp
                                        <li class="path-node path-node--{{ $stateClass }}" data-domain-state="{{ $row['state'] }}">
                                            <span class="path-marker" aria-hidden="true">{{ $marker }}</span>
                                            @if ($state === 'LOCKED')
                                                <article class="path-node-card" aria-label="{{ $mission->title }}, locked">
                                                    <div class="path-node-copy">
                                                        <span class="path-state">LOCKED</span>
                                                        <h4>{{ $mission->order_num }} // {{ $mission->title }}</h4>
                                                        <p>Course access is sealed. Return when Command restores this course.</p>
                                                    </div>
                                                    <x-badge tone="dim">{{ $mission->difficulty }}</x-badge>
                                                </article>
                                            @else
                                                <a
                                                    href="{{ route('mission.show', $mission) }}"
                                                    class="path-node-card"
                                                    aria-label="{{ $mission->title }}, {{ strtolower($state) }}"
                                                >
                                                    <div class="path-node-copy">
                                                        <span class="path-state">{{ $state }}</span>
                                                        <h4>{{ $mission->order_num }} // {{ $mission->title }}</h4>
                                                        @if ($state === 'CURRENT')
                                                            <p>{{ $row['state'] === 'IN PROGRESS' ? 'Saved draft detected. Resume this lesson.' : 'Your next required learning node.' }}</p>
                                                        @elseif ($state === 'COMPLETED')
                                                            <p>Validated and recorded.</p>
                                                        @else
                                                            <p>Lesson available for review.</p>
                                                        @endif
                                                    </div>
                                                    <div class="flex shrink-0 flex-col items-end gap-2">
                                                        <x-badge tone="{{ $mission->difficulty === 'HARD' ? 'alert' : ($mission->difficulty === 'MEDIUM' ? 'amber' : 'cyan') }}">
                                                            {{ $mission->difficulty }}
                                                        </x-badge>
                                                        <span class="path-node-arrow" aria-hidden="true">→</span>
                                                    </div>
                                                </a>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </section>
                        @endforeach

                        @php
                            $bossTone = match ($boss['state']) {
                                'PASSED' => 'phosphor',
                                'AVAILABLE' => 'amber',
                                default => 'dim',
                            };
                        @endphp
                        <section class="boss-node boss-node--{{ strtolower($boss['state']) }}" aria-labelledby="boss-{{ $course->id }}-title">
                            <div class="boss-node-sigil" aria-hidden="true">B</div>
                            <div class="min-w-0 flex-1">
                                <p class="terminal-kicker">FINAL COURSE MILESTONE</p>
                                <h3 id="boss-{{ $course->id }}-title">
                                    {{ $boss['assessment']?->title ?? 'Boss Challenge' }}
                                </h3>
                                <p>{{ $boss['reason'] }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-3">
                                <x-badge tone="{{ $bossTone }}">BOSS {{ $boss['state'] }}</x-badge>
                                @if ($boss['assessment'] !== null && $boss['state'] !== 'LOCKED')
                                    <a href="{{ route('assessment.show', $boss['assessment']) }}" class="terminal-link">
                                        {{ $boss['state'] === 'PASSED' ? 'REVIEW' : 'OPEN' }} →
                                    </a>
                                @endif
                            </div>
                        </section>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endsection
