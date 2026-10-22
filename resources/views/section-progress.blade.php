@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Section Progress')

@section('content')
    @php
        $labels = [
            'DONE' => ['label' => 'Done', 'stamp' => 'atlas-stamp--grn'],
            'IN PROGRESS' => ['label' => 'In progress', 'stamp' => 'atlas-stamp--vio'],
            'NOT STARTED' => ['label' => 'Not started', 'stamp' => 'atlas-stamp--ink'],
            'EMPTY' => ['label' => 'Empty', 'stamp' => 'atlas-stamp--ink'],
        ];
    @endphp
    <div class="atlas-records">
        <header class="atlas-records-heading">
            <h1 class="atlas-records-title">Section progress</h1>
            <p class="atlas-records-lede">Challenge completion inside each course, section by section.</p>
        </header>

        @if ($tree->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="no-sections-title">
                <h2 id="no-sections-title" class="atlas-records-empty-title">No courses yet</h2>
                <p class="atlas-records-empty-copy">No courses are available in your learning path yet. Sections appear here once a course opens.</p>
                <a href="{{ route('learning-path') }}" class="btn btn-secondary">Open learning path <span aria-hidden="true">→</span></a>

            </section>
        @else
            @foreach ($tree as $courseRow)
                @php
                    $course = $courseRow['course'];
                    $courseProgress = $courseRow['progress'];
                @endphp
                <section class="atlas-record-group" aria-labelledby="course-{{ $course->id }}-sections">
                    <div class="atlas-record-group-head">
                        <div>
                            <span class="atlas-record-entry-label">Course {{ str_pad((string) $course->order_num, 2, '0', STR_PAD_LEFT) }}</span>
                            <h2 id="course-{{ $course->id }}-sections" class="atlas-record-group-title">{{ $course->name }}</h2>
                        </div>
                        <span class="atlas-record-group-figure">{{ $courseProgress['completed'] }}/{{ $courseProgress['total'] }} challenges complete</span>
                    </div>
                    @if ($courseRow['sections']->isEmpty())
                        <p class="atlas-record-group-note">No sections have been added to this course.</p>
                    @else
                        <ol class="atlas-records-list">
                            @foreach ($courseRow['sections'] as $sectionRow)
                                @php
                                    $section = $sectionRow['section'];
                                    $sectionProgress = $sectionRow['progress'];
                                    $state = $labels[$sectionRow['state']] ?? ['label' => 'Not started', 'stamp' => 'atlas-stamp--ink'];
                                @endphp
                                <li class="atlas-record-entry atlas-record-entry--compact" data-section-state="{{ strtolower(str_replace(' ', '-', $sectionRow['state'])) }}">
                                    <div class="atlas-record-entry-row">
                                        <h3 class="atlas-record-entry-title">{{ $section?->title ?? 'Additional challenges' }}</h3>
                                        <span class="atlas-record-entry-figure">{{ $sectionProgress['completed'] }}/{{ $sectionProgress['total'] }} · {{ $sectionProgress['percent'] }}%</span>
                                        <span class="atlas-stamp {{ $state['stamp'] }}">@if ($stampIcon = \App\Support\StampIcon::for($state['label']))<x-icon :name="$stampIcon" size="12" />@endif{{ $state['label'] }}</span>
                                    </div>
                                    @if ($sectionProgress['total'] > 0)
                                        <div class="progress atlas-record-entry-bar" role="progressbar" aria-label="{{ $section?->title ?? 'Additional challenges' }} challenge progress" aria-valuemin="0" aria-valuemax="{{ $sectionProgress['total'] }}" aria-valuenow="{{ $sectionProgress['completed'] }}" aria-valuetext="{{ $sectionProgress['completed'] }} of {{ $sectionProgress['total'] }} challenges complete">
                                            <span style="width: {{ $sectionProgress['percent'] }}%"></span>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            @endforeach
        @endif
    </div>
@endsection
