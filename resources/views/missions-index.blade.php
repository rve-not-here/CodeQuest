@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Challenges')

@section('content')
    @php
        $filtersActive = $filters['q'] !== '' || $filters['status'] !== null || $filters['course'] !== null;
        $stateStamps = [
            'COMPLETED' => ['label' => 'Completed', 'stamp' => 'atlas-stamp--grn'],
            'IN PROGRESS' => ['label' => 'In progress', 'stamp' => 'atlas-stamp--vio'],
            'NOT STARTED' => ['label' => 'Available', 'stamp' => 'atlas-stamp--ink'],
            'LOCKED' => ['label' => 'Locked', 'stamp' => 'atlas-stamp--ink'],
        ];
    @endphp
    <div class="atlas-records" data-mission-list>
        <div class="log-running atlas-records-running">
            <span>Records · Challenge index</span>
            <a href="{{ route('learning-path') }}">Learning path →</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Catalogue</span>
            <h1 class="atlas-records-title">Challenges</h1>
            <p class="atlas-records-lede">Practice concepts and track your coding challenges across courses.</p>
            <dl class="log-facts atlas-records-facts">
                <div>
                    <dt>Shown</dt>
                    <dd class="is-numeric">{{ $rows->count() }}</dd>
                </div>
                <div>
                    <dt>Courses</dt>
                    <dd class="is-numeric">{{ $rows->pluck('course.id')->unique()->count() }}</dd>
                </div>
                <div>
                    <dt>XP earned</dt>
                    <dd class="is-numeric">{{ number_format($totalXp) }}</dd>
                </div>
            </dl>
        </header>

        @if ($errors->any())
            <div class="atlas-index-alerts">
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Invalid challenge filters.</strong>
                    <span class="text-fg-muted">{{ $errors->first() }}</span>
                </div>
            </div>
        @endif

        <form method="GET" action="{{ route('missions') }}" class="atlas-index-filters" role="search">
            <div class="atlas-index-filter">
                <label for="mission-search" class="atlas-lesson-entry-label">Search challenges</label>
                <input id="mission-search" name="q" type="search" maxlength="200" value="{{ $filters['q'] }}" class="field w-full" placeholder="Challenge or course" autocomplete="off">
            </div>
            <div class="atlas-index-filter">
                <label for="mission-course" class="atlas-lesson-entry-label">Course</label>
                <select id="mission-course" name="course" class="field w-full">
                    <option value="">All courses</option>
                    @foreach ($courses as $filterCourse)
                        <option value="{{ $filterCourse->id }}" @selected($filters['course'] === $filterCourse->id)>{{ $filterCourse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="atlas-index-filter">
                <label for="mission-state" class="atlas-lesson-entry-label">State</label>
                <select id="mission-state" name="status" class="field w-full">
                    <option value="">All states</option>
                    @foreach (['COMPLETED' => 'Completed', 'IN PROGRESS' => 'In progress', 'NOT STARTED' => 'Not started'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="atlas-index-filter-actions">
                <button type="submit" class="btn btn-primary">Filter challenges</button>
                @if ($filtersActive)
                    <a href="{{ route('missions') }}" class="btn-ghost">Clear filters</a>
                @endif
            </div>
        </form>

        @if ($rows->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="empty-missions-title">
                <h2 id="empty-missions-title" class="atlas-records-empty-title">No challenges match</h2>
                <p class="atlas-records-empty-copy">No challenges match the current filters. Try a different search or clear the filters.</p>
                @if ($filtersActive)
                    <p class="mt-4"><a href="{{ route('missions') }}" class="btn btn-secondary">Clear filters</a></p>
                @endif
            </section>
        @else
            @php
                $courseGroups = $rows->groupBy(fn (array $row): int => $row['course']->id);
            @endphp
            <div class="atlas-index-layout">
                <div class="atlas-index-courses">
                    @foreach ($courseGroups as $courseId => $courseRows)
                        @php
                            $listedCourse = $courseRows->first()['course'];
                            $courseAccessible = $courseRows->first()['accessible'];
                            $sectionGroups = $courseRows->groupBy(fn (array $row): int|string => $row['section']?->id ?? 'additional-'.$courseId);
                        @endphp
                        <section class="atlas-record-group" aria-labelledby="course-{{ $courseId }}-missions">
                            <div class="atlas-record-group-head">
                                <div>
                                    <span class="atlas-record-entry-label">Course {{ str_pad((string) $listedCourse->order_num, 2, '0', STR_PAD_LEFT) }}</span>
                                    <h2 id="course-{{ $courseId }}-missions" class="atlas-record-group-title">{{ $listedCourse->name }}</h2>
                                </div>
                                <span class="atlas-record-group-figure">{{ $courseRows->count() }} shown</span>
                            </div>
                            @foreach ($sectionGroups as $sectionId => $sectionRows)
                                @php
                                    $listedSection = $sectionRows->first()['section'];
                                @endphp
                                <section id="mission-section-{{ $sectionId }}" class="atlas-index-section" aria-labelledby="mission-section-{{ $sectionId }}-title">
                                    <div class="atlas-index-section-head">
                                        <span class="atlas-index-mark" aria-hidden="true">{{ $courseAccessible ? '○' : '×' }}</span>
                                        <h3 id="mission-section-{{ $sectionId }}-title" class="atlas-index-section-title">{{ $listedSection?->title ?? 'Additional challenges' }}</h3>
                                        <span class="atlas-index-section-count">{{ $sectionRows->count() }} shown</span>
                                    </div>
                                    <ol class="atlas-index-list">
                                        @foreach ($sectionRows as $row)
                                            @php
                                                $mission = $row['mission'];
                                                $state = ! $row['accessible'] ? 'LOCKED' : $row['state'];
                                                $isCurrent = $row['accessible'] && $currentMissionId === $mission->id;
                                                $stateView = $stateStamps[$state] ?? $stateStamps['LOCKED'];
                                                $glyph = match ($state) {
                                                    'COMPLETED' => '✓',
                                                    'IN PROGRESS' => '▶',
                                                    'LOCKED' => '×',
                                                    default => '○',
                                                };
                                                $action = match ($state) {
                                                    'COMPLETED' => 'Review',
                                                    'IN PROGRESS' => 'Continue',
                                                    default => 'Start',
                                                };
                                                $reason = $listedCourse->status !== 'active'
                                                    ? 'Course access is sealed. Return when Command restores this course.'
                                                    : 'Complete earlier courses to unlock this challenge.';
                                            @endphp
                                            <li class="atlas-index-row" data-state="{{ $isCurrent ? 'current' : ($state === 'NOT STARTED' ? 'available' : strtolower(str_replace(' ', '-', $state))) }}">
                                                @if ($row['accessible'])
                                                    <a href="{{ route('mission.show', $mission) }}" class="atlas-index-link" @if ($isCurrent) aria-current="step" @endif>
                                                        <span class="atlas-index-node" aria-hidden="true">{{ $glyph }}</span>
                                                        <span class="atlas-index-text">
                                                            <span class="atlas-index-line">
                                                                <span class="atlas-index-number">{{ $mission->order_num }}</span>
                                                                <span class="atlas-index-title">{{ $mission->title }}</span>
                                                            </span>
                                                            @if ($mission->description)
                                                                <span class="atlas-index-desc">{{ $mission->description }}</span>
                                                            @endif
                                                            <span class="atlas-index-meta">{{ $mission->difficulty }} · +{{ $mission->points }} XP</span>
                                                        </span>
                                                        <span class="atlas-index-action">
                                                            @if ($isCurrent)
                                                                <span class="atlas-stamp atlas-stamp--acc">You are here</span>
                                                                <span class="btn btn-primary btn-sm">Continue →</span>
                                                            @else
                                                                <span class="atlas-stamp {{ $stateView['stamp'] }}">{{ $stateView['label'] }}</span>
                                                                <span class="btn btn-secondary btn-sm">{{ $action }} →</span>
                                                            @endif
                                                        </span>
                                                    </a>
                                                @else
                                                    <div class="atlas-index-link atlas-index-link--locked" aria-label="{{ $mission->title }}, locked">
                                                        <span class="atlas-index-node" aria-hidden="true">{{ $glyph }}</span>
                                                        <span class="atlas-index-text">
                                                            <span class="atlas-index-line">
                                                                <span class="atlas-index-number">{{ $mission->order_num }}</span>
                                                                <span class="atlas-index-title">{{ $mission->title }}</span>
                                                            </span>
                                                            <span class="atlas-index-desc">{{ $reason }}</span>
                                                            <span class="atlas-index-meta">{{ $mission->difficulty }} · +{{ $mission->points }} XP</span>
                                                        </span>
                                                        <span class="atlas-index-action">
                                                            <span class="atlas-stamp atlas-stamp--ink">{{ $stateView['label'] }}</span>
                                                        </span>
                                                    </div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                </section>
                            @endforeach
                        </section>
                    @endforeach
                </div>
                <aside class="atlas-index-aside" aria-label="Challenge overview">
                    <nav class="atlas-index-nav" aria-labelledby="mission-overview-heading">
                        <h2 id="mission-overview-heading" class="atlas-index-nav-title">Courses</h2>
                        <ul>
                            @foreach ($courseGroups as $courseId => $courseRows)
                                <li>
                                    <a href="#course-{{ $courseId }}-missions">
                                        <span class="truncate">{{ $courseRows->first()['course']->name }}</span>
                                        <span class="shrink-0">{{ $courseRows->count() }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </aside>
            </div>
        @endif
    </div>
@endsection
