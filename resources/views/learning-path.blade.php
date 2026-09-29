@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Learning Path')

@section('content')
    @php($currentCourseId = $course?->id)

    <nav aria-label="Breadcrumb" class="font-mono text-xs text-fg-subtle">
        <ol class="flex items-center gap-1.5">
            <li><a href="{{ route('dashboard') }}" class="hover:text-fg">Dashboard</a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="text-fg-muted">Learning Path</li>
        </ol>
    </nav>

    <header class="mt-3 mb-7 border-b border-line pb-6">
        <p class="eyebrow">Your journey</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">Learning Path</h1>
        <p class="mt-2 max-w-[64ch] text-sm leading-6 text-fg-muted">Follow your courses from the first mission through the final course milestone.</p>
        <p class="mt-3 font-mono text-xs text-fg-subtle">XP {{ number_format($totalXp) }}</p>
    </header>

    @if ($tree->isEmpty())
        <section class="panel p-5" aria-label="No courses">
            <h2 class="eyebrow">NO COURSES</h2>
            <p class="mt-2 text-sm text-fg-muted">No courses are available yet.</p>
        </section>
    @endif

    @if ($recommendations->isNotEmpty())
        <section class="panel mb-8" aria-labelledby="recommendations-heading">
            <div class="border-b border-line px-5 py-3.5">
                <h2 id="recommendations-heading" class="eyebrow">RECOMMENDED NEXT ACTIONS</h2>
            </div>
            <div class="px-5">
                <x-recommendation-cards :recommendations="$recommendations" variant="student" />
            </div>
        </section>
    @endif

    <div class="space-y-12">
        @foreach ($tree as $courseNode)
            @php
                $pathCourse = $courseNode['course'];
                $courseLocked = $pathCourse->status !== 'active';
                $isCurrentCourse = $currentCourseId === $pathCourse->id && ! $courseLocked;
                $boss = $courseNode['boss'];
                $courseMission = $isCurrentCourse ? $nextMission : null;
            @endphp
            <article aria-labelledby="course-{{ $pathCourse->id }}-title">
                <header class="grid grid-cols-1 gap-6 border-b border-line pb-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-end">
                    <div class="min-w-0">
                        <p class="eyebrow">
                            Course {{ str_pad((string) $pathCourse->order_num, 2, '0', STR_PAD_LEFT) }} · {{ $courseLocked ? 'SEALED' : ($isCurrentCourse ? 'CURRENT SIGNAL' : 'AVAILABLE') }}
                        </p>
                        <h2 id="course-{{ $pathCourse->id }}-title" class="mt-1 text-xl font-semibold tracking-tight text-balance md:text-2xl">{{ $pathCourse->name }}</h2>
                        @if ($pathCourse->description)
                            <p class="mt-2 max-w-[64ch] text-sm leading-6 text-fg-muted">{{ $pathCourse->description }}</p>
                        @endif
                        <p class="mt-3 font-mono text-xs text-fg-subtle">{{ $courseNode['sections']->count() }} {{ $courseNode['sections']->count() === 1 ? 'SECTION' : 'SECTIONS' }} · {{ $courseNode['progress']['total'] }} MISSIONS · BOSS CHALLENGE</p>
                    </div>
                    <div>
                        <div class="mb-2 flex items-baseline justify-between gap-2">
                            <span class="text-sm text-fg-muted">Course progress</span>
                            <span class="font-mono text-xs text-fg-subtle"><span class="text-base font-medium text-fg">{{ $courseNode['progress']['percent'] }}%</span> · {{ $courseNode['progress']['completed'] }} / {{ $courseNode['progress']['total'] }}</span>
                        </div>
                        <div class="progress" role="progressbar" aria-label="{{ $pathCourse->name }} progress" aria-valuemin="0" aria-valuemax="{{ $courseNode['progress']['total'] }}" aria-valuenow="{{ $courseNode['progress']['completed'] }}" aria-valuetext="{{ $courseNode['progress']['completed'] }} of {{ $courseNode['progress']['total'] }} missions complete, {{ $courseNode['progress']['percent'] }} percent"><span style="width: {{ $courseNode['progress']['percent'] }}%"></span></div>
                        @if ($courseMission !== null)
                            <a href="{{ route('mission.show', $courseMission) }}" class="btn btn-primary mt-4 w-full">Continue {{ $courseMission->title }} →</a>
                        @endif
                    </div>
                </header>

                <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_16rem]">
                    <div class="flex min-w-0 flex-col gap-4">
                        @foreach ($courseNode['sections'] as $sectionNode)
                            @php
                                $pathSection = $sectionNode['section'];
                                $sectionCurrent = ! $courseLocked && $nextMission !== null && $sectionNode['missions']->contains(fn ($row) => $row['mission']->id === $nextMission->id);
                                $sectionComplete = $sectionNode['progress']['total'] > 0 && $sectionNode['progress']['completed'] === $sectionNode['progress']['total'];
                            @endphp
                            <details id="section-{{ $pathSection->id }}" class="panel group {{ $sectionCurrent ? 'border-accent-line' : '' }}" @if ($sectionCurrent) open @endif>
                                <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3.5 md:px-5 [&::-webkit-details-marker]:hidden">
                                    <span class="grid size-6 shrink-0 place-items-center rounded-full border {{ $sectionComplete ? 'border-accent-line bg-accent-soft text-accent' : ($sectionCurrent ? 'border-accent text-accent' : 'border-line text-locked') }} font-mono text-xs" aria-hidden="true">{{ $sectionComplete ? '✓' : ($sectionCurrent ? '●' : '○') }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="eyebrow block">Section {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}@if ($sectionCurrent) · In Progress @elseif ($courseLocked) · Locked @endif</span>
                                        <span class="block truncate text-[15px] font-medium">{{ $pathSection->title }}</span>
                                    </span>
                                    @if ($sectionComplete)
                                        <span class="badge badge-accent hidden sm:inline-flex">Completed</span>
                                    @endif
                                    <span class="shrink-0 font-mono text-xs text-fg-subtle">{{ $sectionNode['progress']['completed'] }} / {{ $sectionNode['progress']['total'] }}</span>
                                    <span class="text-fg-subtle transition-transform group-open:rotate-180" aria-hidden="true">⌄</span>
                                </summary>
                                <ol class="rail border-t border-line px-4 pb-2 md:px-5" aria-label="{{ $pathSection->title }} missions">
                                    @foreach ($sectionNode['missions'] as $row)
                                        @php
                                            $mission = $row['mission'];
                                            $isCurrent = ! $courseLocked && $nextMission !== null && $nextMission->id === $mission->id;
                                            $state = $courseLocked ? 'LOCKED' : ($row['state'] === 'COMPLETED' ? 'COMPLETED' : ($isCurrent ? 'CURRENT' : 'AVAILABLE'));
                                        @endphp
                                        <li data-state="{{ strtolower($state) }}" data-domain-state="{{ $row['state'] }}">
                                            <span class="node font-mono text-xs" aria-hidden="true">{{ match ($state) { 'COMPLETED' => '✓', 'CURRENT' => '▶', 'AVAILABLE' => '○', default => '×' } }}</span>
                                            @if ($state === 'CURRENT')
                                                <div class="my-1.5 rounded-sm border border-accent-line bg-accent-soft/60 p-3 md:p-4">
                                                    <div class="flex flex-wrap items-center gap-2"><span class="badge badge-accent">CURRENT · You are here</span><span class="badge badge-neutral">{{ $mission->difficulty }}</span></div>
                                                    <h3 class="mt-2 text-[15px] font-semibold text-fg">{{ $mission->title }}</h3>
                                                    <p class="mt-1 text-sm text-fg-muted">{{ $row['state'] === 'IN PROGRESS' ? 'Saved draft detected. Resume this lesson.' : 'Your next required learning node.' }}</p>
                                                    <div class="mt-3 flex flex-wrap items-center gap-3">
                                                        <a href="{{ route('mission.show', $mission) }}" class="btn btn-primary btn-sm">Continue →</a>
                                                        <span class="font-mono text-xs text-fg-subtle">{{ $mission->difficulty }} · +{{ $mission->points }} XP</span>
                                                    </div>
                                                </div>
                                            @elseif ($state === 'LOCKED')
                                                <div class="flex min-w-0 items-center gap-3 py-3 text-sm text-fg-subtle" aria-disabled="true">
                                                    <span class="font-mono text-2xs text-locked">{{ $mission->order_num }}</span>
                                                    <span class="min-w-0 flex-1"><span class="block">{{ $mission->title }}</span><span class="block text-xs">Course access is sealed. Return when Command restores this course.</span></span>
                                                    <span class="badge badge-locked">LOCKED</span>
                                                </div>
                                            @else
                                                <a href="{{ route('mission.show', $mission) }}" class="flex min-w-0 items-center gap-3 py-3 text-sm hover:text-accent" aria-label="{{ $mission->title }}, {{ strtolower($state) }}">
                                                    <span class="font-mono text-2xs text-fg-subtle">{{ $mission->order_num }}</span>
                                                    <span class="min-w-0 flex-1"><span class="block {{ $state === 'COMPLETED' ? 'text-fg-muted' : 'text-fg' }}">{{ $mission->title }}</span>@if ($state === 'COMPLETED')<span class="block text-xs text-fg-subtle">Validated and recorded.</span>@endif</span>
                                                    <span class="font-mono text-2xs text-fg-subtle">{{ $state }} · {{ $mission->difficulty }}</span>
                                                </a>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </details>
                        @endforeach

                        <section aria-labelledby="boss-{{ $pathCourse->id }}-title" class="flex items-start gap-3 rounded-md border border-dashed border-line-strong px-4 py-4 md:px-5">
                            <span class="grid size-6 shrink-0 place-items-center rounded-xs border border-line text-fg-muted" aria-hidden="true">◆</span>
                            <div class="min-w-0 flex-1">
                                <p class="eyebrow">FINAL COURSE MILESTONE · BOSS {{ $boss['state'] }}</p>
                                <h3 id="boss-{{ $pathCourse->id }}-title" class="mt-1 text-[15px] font-medium text-fg">{{ $boss['assessment']?->title ?? 'Boss Challenge' }}</h3>
                                <p class="mt-1 text-sm text-fg-muted">{{ $boss['reason'] }}</p>
                                @if ($boss['assessment'] !== null && $boss['state'] !== 'LOCKED')
                                    <a href="{{ route('assessment.show', $boss['assessment']) }}" class="btn btn-secondary btn-sm mt-3">{{ $boss['state'] === 'PASSED' ? 'Review challenge' : 'START CHALLENGE' }} →</a>
                                @endif
                            </div>
                        </section>
                    </div>

                    <aside class="hidden lg:block" aria-label="{{ $pathCourse->name }} overview">
                        <nav class="sticky top-20" aria-labelledby="sections-{{ $pathCourse->id }}-heading">
                            <h3 id="sections-{{ $pathCourse->id }}-heading" class="eyebrow mb-2">Sections</h3>
                            <ul class="border-l border-line text-sm">
                                @foreach ($courseNode['sections'] as $sectionNode)
                                    @php($overviewSection = $sectionNode['section'])
                                    <li><a href="#section-{{ $overviewSection->id }}" class="-ml-px flex justify-between gap-2 border-l border-transparent py-1.5 pr-1 pl-3 text-fg-muted hover:border-accent hover:text-fg"><span class="truncate">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} {{ $overviewSection->title }}</span><span class="shrink-0 font-mono text-2xs">{{ $sectionNode['progress']['completed'] }}/{{ $sectionNode['progress']['total'] }}</span></a></li>
                                @endforeach
                            </ul>
                        </nav>
                    </aside>
                </div>
            </article>
        @endforeach
    </div>
@endsection
