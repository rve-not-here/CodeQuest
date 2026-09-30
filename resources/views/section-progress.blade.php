@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Section Progress')

@section('content')
    <div class="mx-auto w-full max-w-[1080px]">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
            <div>
                <p class="eyebrow">Learning record</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">Section Progress</h1>
                <p class="mt-2 max-w-[64ch] text-sm leading-6 text-fg-muted">Track mission completion within each course section.</p>
            </div>
            <a href="{{ route('learning-path') }}" class="btn btn-secondary btn-sm">View Learning Path →</a>
        </header>

        @if ($tree->isEmpty())
            <section class="panel mt-6 px-6 py-10 text-center" aria-labelledby="no-sections-title">
                <h2 id="no-sections-title" class="text-lg font-semibold">NO COURSES</h2>
                <p class="mt-2 text-sm text-fg-muted">No courses are available in your learning path yet.</p>
            </section>
        @else
            <div class="mt-6 space-y-8">
                @foreach ($tree as $courseRow)
                    @php
                        $course = $courseRow['course'];
                        $courseProgress = $courseRow['progress'];
                    @endphp
                    <section aria-labelledby="course-{{ $course->id }}-sections">
                        <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                            <div>
                                <p class="eyebrow">Course {{ str_pad((string) $course->order_num, 2, '0', STR_PAD_LEFT) }}</p>
                                <h2 id="course-{{ $course->id }}-sections" class="mt-1 text-lg font-semibold">{{ $course->name }}</h2>
                            </div>
                            <span class="font-mono text-xs text-fg-subtle">{{ $courseProgress['completed'] }}/{{ $courseProgress['total'] }} missions complete</span>
                        </div>
                        @if ($courseRow['sections']->isEmpty())
                            <div class="panel px-5 py-6 text-sm text-fg-muted">No sections have been added to this course.</div>
                        @else
                            <ol class="grid grid-cols-1 gap-3">
                                @foreach ($courseRow['sections'] as $sectionRow)
                                    @php
                                        $section = $sectionRow['section'];
                                        $sectionProgress = $sectionRow['progress'];
                                        $state = $sectionRow['state'];
                                    @endphp
                                    <li class="panel min-w-0 px-5 py-4 md:px-6" data-section-state="{{ strtolower(str_replace(' ', '-', $state)) }}">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="eyebrow">Section {{ str_pad((string) $section->order_num, 2, '0', STR_PAD_LEFT) }}</p>
                                                <h3 class="mt-1 text-[15px] font-semibold text-balance">{{ $section->title }}</h3>
                                            </div>
                                            <span class="badge {{ $state === 'DONE' ? 'badge-accent' : ($state === 'IN PROGRESS' ? 'badge-warning' : 'badge-neutral') }}">{{ $state }}</span>
                                        </div>
                                        <div class="mt-4 flex flex-wrap items-baseline justify-between gap-2 text-sm">
                                            <span class="font-mono text-fg">{{ $sectionProgress['completed'] }}/{{ $sectionProgress['total'] }} missions complete</span>
                                            <span class="font-mono text-fg-muted">{{ $sectionProgress['percent'] }}%</span>
                                        </div>
                                        @if ($sectionProgress['total'] > 0)
                                            <div class="progress mt-2" role="progressbar" aria-label="{{ $section->title }} mission progress" aria-valuemin="0" aria-valuemax="{{ $sectionProgress['total'] }}" aria-valuenow="{{ $sectionProgress['completed'] }}" aria-valuetext="{{ $sectionProgress['completed'] }} of {{ $sectionProgress['total'] }} missions complete">
                                                <span style="width: {{ $sectionProgress['percent'] }}%"></span>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </div>
@endsection
