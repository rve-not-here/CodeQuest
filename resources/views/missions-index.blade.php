@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Challenges')

@section('content')
    <header class="grid grid-cols-1 gap-6 border-b border-line pb-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-end">
        <div>
            <p class="eyebrow">Learn / Challenges</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">Challenges</h1>
            <p class="mt-2 max-w-[62ch] text-sm leading-6 text-fg-muted">Practice concepts and track your coding challenges across courses.</p>
        </div>
        <div class="lg:text-right">
            <p class="eyebrow">Your learning record</p>
            <p class="mt-1 font-mono text-sm text-fg">{{ $rows->count() }} {{ $rows->count() === 1 ? 'mission' : 'missions' }} shown · XP {{ number_format($totalXp) }}</p>
            <a href="{{ route('learning-path') }}" class="btn btn-quiet btn-sm mt-3">View Learning Path →</a>
        </div>
    </header>

    @if ($errors->any())
        <x-status-message type="error" title="Invalid challenge filters" class="mt-5">{{ $errors->first() }}</x-status-message>
    @endif

    <form method="GET" action="{{ route('missions') }}" class="panel mt-5 p-4" role="search">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div>
                <label for="mission-search" class="eyebrow">Search challenges</label>
                <input id="mission-search" name="q" type="search" maxlength="200" value="{{ $filters['q'] }}" class="field mt-1.5 w-full" placeholder="Challenge or course" autocomplete="off">
            </div>
            <div>
                <label for="mission-course" class="eyebrow">Course</label>
                <select id="mission-course" name="course" class="field mt-1.5 w-full">
                    <option value="">All courses</option>
                    @foreach ($courses as $filterCourse)
                        <option value="{{ $filterCourse->id }}" @selected($filters['course'] === $filterCourse->id)>{{ $filterCourse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="mission-state" class="eyebrow">State</label>
                <select id="mission-state" name="status" class="field mt-1.5 w-full">
                    <option value="">All states</option>
                    @foreach (['COMPLETED' => 'Completed', 'IN PROGRESS' => 'In Progress', 'NOT STARTED' => 'Not Started'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Filter challenges</button>
            @if ($filters['q'] !== '' || $filters['status'] !== null || $filters['course'] !== null)
                <a href="{{ route('missions') }}" class="btn btn-ghost btn-sm">Clear filters</a>
            @endif
        </div>
    </form>

    @if ($rows->isEmpty())
        <section class="panel mt-5 px-6 py-10 text-center" aria-labelledby="empty-missions-title">
            <h2 id="empty-missions-title" class="text-[15px] font-medium">NO CHALLENGES</h2>
            <p class="mt-2 text-sm text-fg-muted">No challenges match the current filters.</p>
            @if ($filters['q'] !== '' || $filters['status'] !== null || $filters['course'] !== null)
                <a href="{{ route('missions') }}" class="btn btn-secondary btn-sm mt-4">Clear filters</a>
            @endif
        </section>
    @else
        @php
            $courseGroups = $rows->groupBy(fn (array $row): int => $row['course']->id);
        @endphp
        <div data-mission-list class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_16rem]">
            <div class="flex min-w-0 flex-col gap-6">
                @foreach ($courseGroups as $courseId => $courseRows)
                    @php
                        $listedCourse = $courseRows->first()['course'];
                        $courseAccessible = $courseRows->first()['accessible'];
                        $sectionGroups = $courseRows->groupBy(fn (array $row): int|string => $row['section']?->id ?? 'additional-'.$courseId);
                    @endphp
                    <section aria-labelledby="course-{{ $courseId }}-missions">
                        <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                            <div>
                                <p class="eyebrow">Course {{ str_pad((string) $listedCourse->order_num, 2, '0', STR_PAD_LEFT) }} · {{ $courseAccessible ? 'AVAILABLE' : 'LOCKED' }}</p>
                                <h2 id="course-{{ $courseId }}-missions" class="mt-1 text-lg font-semibold">{{ $listedCourse->name }}</h2>
                            </div>
                            <span class="font-mono text-xs text-fg-subtle">{{ $courseRows->count() }} shown</span>
                        </div>
                        <div class="flex flex-col gap-4">
                            @foreach ($sectionGroups as $sectionId => $sectionRows)
                                @php
                                    $listedSection = $sectionRows->first()['section'];
                                @endphp
                                <section id="mission-section-{{ $sectionId }}" class="panel" aria-labelledby="mission-section-{{ $sectionId }}-title">
                                    <div class="flex flex-wrap items-center gap-3 border-b border-line px-4 py-3.5 md:px-5">
                                        <span class="grid size-6 shrink-0 place-items-center rounded-full border border-line text-fg-muted" aria-hidden="true">{{ $courseAccessible ? '○' : '×' }}</span>
                                        <div class="min-w-0 flex-1">
                                            <p class="eyebrow">Section {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                                            <h3 id="mission-section-{{ $sectionId }}-title" class="text-[15px] font-medium">{{ $listedSection?->title ?? 'Additional challenges' }}</h3>
                                        </div>
                                        <span class="font-mono text-xs text-fg-subtle">{{ $sectionRows->count() }} shown</span>
                                    </div>
                                    <ol class="px-2 py-1 md:px-3">
                                        @foreach ($sectionRows as $row)
                                            @php
                                                $mission = $row['mission'];
                                                $state = ! $row['accessible'] ? 'LOCKED' : $row['state'];
                                                $isCurrent = $row['accessible'] && $currentMissionId === $mission->id;
                                            @endphp
                                            <li class="mission-row {{ $isCurrent ? 'is-current' : '' }}" data-state="{{ $isCurrent ? 'current' : ($state === 'NOT STARTED' ? 'available' : strtolower(str_replace(' ', '-', $state))) }}">
                                                <span class="node font-mono text-xs" aria-hidden="true">{{ match ($state) { 'COMPLETED' => '✓', 'IN PROGRESS' => '▶', 'LOCKED' => '×', default => '○' } }}</span>
                                                @if ($row['accessible'])
                                                    <a href="{{ route('mission.show', $mission) }}" class="mission-body" @if ($isCurrent) aria-current="step" @endif>
                                                        <span class="mission-id">{{ $mission->order_num }}</span>
                                                        <span class="mission-text">
                                                            @if ($isCurrent)<span class="badge badge-accent w-fit">You are here</span>@endif
                                                            <span class="mission-title">{{ $mission->title }}</span>
                                                            @if ($mission->description)<span class="mission-desc">{{ $mission->description }}</span>@endif
                                                        </span>
                                                        <span class="mission-foot">
                                                            <span class="mission-meta">{{ $mission->difficulty }} · +{{ $mission->points }} XP</span>
                                                            <span class="mission-action"><span class="mission-state">{{ $isCurrent ? 'CURRENT' : ($state === 'NOT STARTED' ? 'Available' : $state) }}</span><span class="btn btn-secondary btn-sm">{{ $state === 'COMPLETED' ? 'Review' : ($isCurrent ? 'Continue' : 'Start') }} →</span></span>
                                                        </span>
                                                    </a>
                                                @else
                                                    <div class="mission-body" aria-label="{{ $mission->title }}, locked">
                                                        <span class="mission-id">{{ $mission->order_num }}</span>
                                                        <span class="mission-text"><span class="mission-title">{{ $mission->title }}</span><span class="mission-desc">{{ $listedCourse->status !== 'active' ? 'Course access is sealed. Return when Command restores this course.' : 'Complete earlier courses to unlock this challenge.' }}</span></span>
                                                        <span class="mission-foot"><span class="mission-meta">{{ $mission->difficulty }} · +{{ $mission->points }} XP</span><span class="mission-action"><span class="badge badge-locked">LOCKED</span></span></span>
                                                    </div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                </section>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
            <aside class="hidden lg:block" aria-label="Challenge overview">
                <nav class="sticky top-20" aria-labelledby="mission-overview-heading">
                    <h2 id="mission-overview-heading" class="eyebrow mb-2">Courses</h2>
                    <ul class="border-l border-line text-sm">
                        @foreach ($courseGroups as $courseId => $courseRows)
                            <li><a href="#course-{{ $courseId }}-missions" class="-ml-px flex justify-between gap-2 border-l border-transparent py-1.5 pr-1 pl-3 text-fg-muted hover:border-accent hover:text-fg"><span class="truncate">{{ $courseRows->first()['course']->name }}</span><span class="shrink-0 font-mono text-2xs">{{ $courseRows->count() }}</span></a></li>
                        @endforeach
                    </ul>
                </nav>
            </aside>
        </div>
    @endif
@endsection
