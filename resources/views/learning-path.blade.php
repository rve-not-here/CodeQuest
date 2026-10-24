@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Learning Path')

@section('content')
    <div class="atlas-path">
        <h1 class="sr-only">Learning path</h1>
        @php
            $totalCompleted = 0;
            $totalChallenges = 0;
            foreach ($tree as $courseNode) {
                $totalCompleted += $courseNode['progress']['completed'];
                $totalChallenges += $courseNode['progress']['total'];
            }
            $overallPercent = $totalChallenges > 0 ? (int) round($totalCompleted / $totalChallenges * 100) : 0;

            // The entry plate names the current challenge by its position in its course.
            $entryPosition = 0;
            $entryTotal = 0;
            if ($nextMission !== null) {
                foreach ($tree as $courseNode) {
                    $courseMissions = collect($courseNode['sections'])
                        ->flatMap(fn (array $sectionNode) => $sectionNode['missions'])
                        ->values();
                    $missionIndex = $courseMissions->search(
                        fn (array $row): bool => $row['mission']->id === $nextMission->id
                    );

                    if ($missionIndex !== false) {
                        $entryPosition = (int) $missionIndex + 1;
                        $entryTotal = $courseMissions->count();
                        break;
                    }
                }
            }
        @endphp

        <section class="atlas-path-summary" aria-label="Overall progress">
            <span class="atlas-path-summary-xp">XP {{ number_format($totalXp) }}</span>
            <span class="atlas-path-summary-count">{{ $totalCompleted }} of {{ $totalChallenges }} challenges logged · {{ $overallPercent }}%</span>
            <div class="atlas-path-meter" role="progressbar" aria-label="Overall learning progress" aria-valuemin="0" aria-valuemax="{{ $totalChallenges }}" aria-valuenow="{{ $totalCompleted }}" aria-valuetext="{{ $totalCompleted }} of {{ $totalChallenges }} challenges complete"><span style="width: {{ $overallPercent }}%"></span></div>
            @if ($totalChallenges > 0)
                <p class="atlas-path-summary-note">Mission order is a guide. Available challenges can be opened now.</p>
            @endif
            @if ($nextMission !== null)
                <a href="{{ route('mission.show', $nextMission) }}" class="atlas-path-summary-jump">Next up: {{ $nextMission->title }} <x-icon name="arrow-right" data-arrow size="14" /></a>
            @endif
        </section>

        @if ($tree->isEmpty())
            <section class="atlas-path-empty" aria-label="No courses">
                <h2>No courses</h2>
                <p>No courses are available yet.</p>
                <p class="game-copy-line">{{ \App\Support\GameCopy::line('empty.courses') }}</p>
            </section>
        @else
            <div class="atlas-path-layout">
                <aside class="atlas-path-rail" aria-label="Course index">
                    <p class="atlas-path-rail-label">Index</p>
                    @foreach ($tree as $courseNode)
                        @php
                            $railCourse = $courseNode['course'];
                            $railLocked = ! $courseNode['accessible'];
                            $railCurrent = $course?->id === $railCourse->id && ! $railLocked;
                            $railState = $railLocked ? 'Locked' : ($courseNode['boss']['state'] === 'PASSED' ? 'Complete' : ($railCurrent ? 'Current' : 'Available'));
                        @endphp
                        <a class="atlas-path-rail-item {{ $railCurrent ? 'atlas-path-rail-item--now' : '' }} {{ $railLocked ? 'atlas-path-rail-item--locked' : '' }}" href="#course-{{ $railCourse->id }}" @if ($railCurrent) aria-current="location" @endif>
                            <span class="atlas-path-rail-number">Course {{ str_pad((string) $railCourse->order_num, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="atlas-path-rail-title">{{ $railCourse->name }}</span>
                            <span class="atlas-path-rail-figures">{{ $courseNode['progress']['completed'] }} / {{ $courseNode['progress']['total'] }}@if ($courseNode['progress']['total'] > 0) · {{ $courseNode['progress']['percent'] }}%@endif · {{ $railState }}</span>
                            @if ($railCurrent)
                                <span class="atlas-path-rail-now">You are here</span>
                            @endif
                        </a>
                    @endforeach
                    @if ($course !== null)
                        @php
                            $currentCourseNode = $tree->first(fn (array $courseNode): bool => $courseNode['course']->id === $course->id);
                        @endphp
                        @if ($currentCourseNode !== null && $currentCourseNode['boss']['assessment'] !== null)
                            <a class="atlas-path-rail-item atlas-path-rail-item--quiet" href="#boss-{{ $course->id }}">
                                <span class="atlas-path-rail-number">Milestone</span>
                                <span class="atlas-path-rail-title">{{ $currentCourseNode['boss']['assessment']->title }}</span>
                                <span class="atlas-path-rail-figures">{{ ucfirst(strtolower($currentCourseNode['boss']['state'])) }}</span>
                            </a>
                        @endif
                    @endif
                </aside>

                <div class="atlas-path-column">
                    @foreach ($tree as $courseNode)
                        @php
                            $pathCourse = $courseNode['course'];
                            $courseLocked = ! $courseNode['accessible'];
                            $isCurrentCourse = $course?->id === $pathCourse->id && ! $courseLocked;
                            $boss = $courseNode['boss'];
                            $courseState = $courseLocked ? 'Locked' : ($boss['state'] === 'PASSED' ? 'Complete' : ($isCurrentCourse ? 'Current' : 'Available'));
                            // The course being worked on (and a sealed one, whose only content is its reason)
                            // starts expanded; every other chapter stays one click away.
                            $courseOpen = $isCurrentCourse || $courseLocked;
                        @endphp
                        <details id="course-{{ $pathCourse->id }}" class="atlas-path-course"{{ $courseOpen ? ' open' : '' }}>
                            <summary class="atlas-path-course-head">
                                <h2 id="course-{{ $pathCourse->id }}-title">{{ $pathCourse->name }}</h2>
                                <span class="atlas-path-course-state">Course {{ str_pad((string) $pathCourse->order_num, 2, '0', STR_PAD_LEFT) }} · {{ $courseState }}</span>
                                <span class="atlas-path-course-figure">{{ $courseNode['progress']['completed'] }} / {{ $courseNode['progress']['total'] }}@if ($courseNode['progress']['total'] > 0) · {{ $courseNode['progress']['percent'] }}%@endif</span>
                                <span class="atlas-path-course-toggle" aria-hidden="true"><x-icon name="chevron-down" size="16" /></span>
                            </summary>
                            <div class="atlas-path-course-body">
                                @if ($pathCourse->description)
                                    <p class="atlas-path-description">{{ $pathCourse->description }}</p>
                                @endif

                                @if ($courseLocked)
                                    <p class="atlas-path-lock-reason">{{ $courseNode['locked_reason'] }}</p>
                                @else
                                    @foreach ($courseNode['sections'] as $sectionNode)
                                        @php
                                            $pathSection = $sectionNode['section'];
                                            $sectionTitle = $pathSection?->title ?? 'Additional challenges';
                                            $sectionKey = $pathSection?->id ?? 'additional-'.$pathCourse->id;
                                        @endphp
                                        <section id="section-{{ $sectionKey }}" class="atlas-path-section" aria-label="{{ $sectionTitle }}">
                                            <div class="atlas-path-checkpoint">
                                                <h3>{{ $sectionTitle }}</h3>
                                                <span class="atlas-path-checkpoint-meta">{{ $sectionNode['progress']['completed'] }} / {{ $sectionNode['progress']['total'] }}</span>
                                            </div>
                                            <ol class="atlas-path-missions">
                                                @foreach ($sectionNode['missions'] as $row)
                                                    @php
                                                        $mission = $row['mission'];
                                                        $isCurrent = $nextMission !== null && $nextMission->id === $mission->id;
                                                        $state = $row['state'] === 'COMPLETED' ? 'Completed' : ($isCurrent ? 'Current' : 'Available');
                                                    @endphp
                                                    @if ($isCurrent && $entryTotal > 0)
                                                        <li class="atlas-path-mission atlas-path-entry-mission" data-state="current" data-domain-state="{{ $row['state'] }}" aria-current="step">
                                                            <article class="atlas-path-entry" aria-labelledby="current-challenge-title">
                                                                <header class="atlas-path-entry-head">
                                                                    <p class="atlas-path-entry-line">
                                                                        <span class="atlas-path-entry-number">Entry {{ str_pad((string) $entryPosition, 3, '0', STR_PAD_LEFT) }}</span>
                                                                        <span class="atlas-path-entry-divider" aria-hidden="true">/</span>
                                                                        <span>Current challenge</span>
                                                                    </p>
                                                                    @if ($row['state'] === 'IN PROGRESS')
                                                                        <span class="atlas-stamp atlas-stamp--acc">Draft saved</span>
                                                                    @endif
                                                                </header>
                                                                <div class="atlas-path-entry-body">
                                                                    <div class="atlas-path-entry-copy">
                                                                        <p class="atlas-path-entry-context">
                                                                            Course {{ str_pad((string) $pathCourse->order_num, 2, '0', STR_PAD_LEFT) }}
                                                                            <span class="atlas-path-entry-divider" aria-hidden="true">/</span>
                                                                            <span class="atlas-path-entry-section">{{ $sectionTitle }}</span>
                                                                        </p>
                                                                        <h3 id="current-challenge-title">@foreach (explode('_', $mission->title) as $part)@unless ($loop->first)_<wbr>@endunless{{ $part }}@endforeach</h3>
                                                                        @if ($row['state'] === 'IN PROGRESS')
                                                                            <p class="atlas-path-entry-note">Saved draft detected. Resume this lesson.</p>
                                                                        @endif
                                                                        <dl class="atlas-path-entry-chips">
                                                                            <div class="atlas-path-entry-chip">
                                                                                <dt>Difficulty</dt>
                                                                                <dd>{{ $mission->difficulty }}</dd>
                                                                            </div>
                                                                            <div class="atlas-path-entry-chip">
                                                                                <dt>Completion reward</dt>
                                                                                <dd>+{{ $mission->points }} XP</dd>
                                                                            </div>
                                                                        </dl>
                                                                    </div>
                                                                    <aside class="atlas-path-entry-order" aria-label="Position in course">
                                                                        <p class="atlas-path-entry-kicker">Challenges logged</p>
                                                                        <p class="atlas-path-entry-position" aria-hidden="true">
                                                                            <span class="atlas-path-entry-position-number">{{ str_pad((string) $entryPosition, 2, '0', STR_PAD_LEFT) }}</span>
                                                                            <span class="atlas-path-entry-position-total">/ {{ str_pad((string) $entryTotal, 2, '0', STR_PAD_LEFT) }}</span>
                                                                        </p>
                                                                        <div class="atlas-path-ruler" aria-hidden="true">
                                                                            @for ($step = 1; $step <= $entryTotal; $step++)
                                                                                <span class="atlas-path-ruler-step {{ $step === $entryPosition ? 'is-current' : ($step <= $courseNode['progress']['completed'] ? 'is-complete' : '') }}"></span>
                                                                            @endfor
                                                                        </div>
                                                                        <p class="atlas-path-entry-caption">Challenge {{ $entryPosition }} of {{ $entryTotal }}</p>
                                                                        <div class="atlas-path-entry-actions">
                                                                            <a href="{{ route('mission.show', $mission) }}" class="btn btn-primary btn-lg">{{ $row['state'] === 'IN PROGRESS' ? 'Resume challenge' : 'Start challenge' }} →</a>
                                                                        </div>
                                                                    </aside>
                                                                </div>
                                                            </article>
                                                        </li>
                                                    @else
                                                        <li class="atlas-path-mission" data-state="{{ strtolower($state) }}" data-domain-state="{{ $row['state'] }}">
                                                            <span class="atlas-path-marker {{ $state === 'Completed' ? 'is-complete' : '' }}" aria-hidden="true">@if ($state === 'Completed')<x-icon name="check" size="14" />@endif</span>
                                                            <h4>@foreach (explode('_', $mission->title) as $part)@unless ($loop->first)_<wbr>@endunless{{ $part }}@endforeach</h4>
                                                            <p class="atlas-path-mission-meta"><span class="atlas-path-state">{{ $state }}</span> · {{ $mission->difficulty }} · +{{ $mission->points }} XP <a href="{{ route('mission.show', $mission) }}" class="atlas-path-mission-link @if ($state === 'Completed') is-review @endif" @if ($state === 'Completed') aria-label="Review: {{ $mission->title }}" @endif>{{ $state === 'Completed' ? 'Review' : 'Open lesson' }} →</a></p>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ol>
                                        </section>
                                    @endforeach

                                    <section id="boss-{{ $pathCourse->id }}" class="atlas-path-boss" aria-label="Final course milestone">
                                        <p class="atlas-path-boss-label">Final course milestone</p>
                                        @if ($boss['assessment'] !== null)
                                            <h3>{{ $boss['assessment']->title }}</h3>
                                        @endif
                                        <p class="atlas-path-note">{{ $boss['reason'] }}</p>
                                        <div class="atlas-path-boss-act">
                                            @if ($boss['state'] === 'PASSED')
                                                <span class="atlas-stamp atlas-stamp--grn"><x-icon name="check" size="12" />Passed</span>
                                            @elseif ($boss['state'] === 'AVAILABLE')
                                                <span class="atlas-stamp atlas-stamp--ink"><x-icon name="circle-dot" size="12" />Available</span>
                                            @elseif ($boss['assessment'] !== null)
                                                <span class="atlas-stamp atlas-stamp--ink"><x-icon name="lock" size="12" />Locked</span>
                                            @endif
                                            @if ($boss['assessment'] !== null && $boss['accessible'])
                                                <a href="{{ route('assessment.show', $boss['assessment']) }}" class="btn btn-secondary">{{ $boss['state'] === 'PASSED' ? 'Review challenge' : 'Start challenge' }} →</a>
                                            @endif
                                        </div>
                                    </section>
                                @endif
                            </div>
                        </details>
                    @endforeach

                    @if ($recommendations->isNotEmpty())
                        <section class="atlas-path-recommendations" aria-labelledby="recommendations-heading">
                            <h2 id="recommendations-heading">Recommended next actions</h2>
                            <x-recommendation-cards :recommendations="$recommendations" />
                        </section>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
