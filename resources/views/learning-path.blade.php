@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Learning Path')

@section('content')
    <div class="atlas-path">
        @php
            $totalCompleted = 0;
            $totalChallenges = 0;
            foreach ($tree as $courseNode) {
                $totalCompleted += $courseNode['progress']['completed'];
                $totalChallenges += $courseNode['progress']['total'];
            }
            $overallPercent = $totalChallenges > 0 ? (int) round($totalCompleted / $totalChallenges * 100) : 0;
        @endphp

        <div class="log-running">
            <span>Log index · {{ $tree->count() }} {{ $tree->count() === 1 ? 'course' : 'courses' }} · {{ $totalCompleted }} / {{ $totalChallenges }} challenges</span>
            <a href="{{ route('timeline') }}">View learning record →</a>
        </div>

        <header class="atlas-path-heading">
            <div><h1>Learning Path</h1><p>Your courses, challenges, and course-level assessments.</p></div>
            <span class="atlas-path-meta">XP {{ number_format($totalXp) }}</span>
        </header>

        @if ($nextMission !== null)
            <section class="atlas-path-continue" aria-labelledby="continue-heading">
                <p class="atlas-path-meta">Continue where you left off</p>
                <h2 id="continue-heading">@foreach (explode('_', $nextMission->title) as $part)@unless ($loop->first)_<wbr>@endunless{{ $part }}@endforeach</h2>
                <p class="atlas-path-meta">{{ $nextMission->difficulty }} · +{{ $nextMission->points }} XP</p>
                <a href="{{ route('mission.show', $nextMission) }}" class="btn btn-primary">Continue →</a>
            </section>
        @endif

        @if ($totalChallenges > 0)
            <section class="atlas-path-overall" aria-label="Overall progress">
                <p class="atlas-path-meta">{{ $totalCompleted }} of {{ $totalChallenges }} challenges complete across your courses · {{ $overallPercent }}%</p>
                <div class="progress" role="progressbar" aria-label="Overall learning progress" aria-valuemin="0" aria-valuemax="{{ $totalChallenges }}" aria-valuenow="{{ $totalCompleted }}" aria-valuetext="{{ $totalCompleted }} of {{ $totalChallenges }} challenges complete"><span style="width: {{ $overallPercent }}%"></span></div>
            </section>
        @endif

        @if ($tree->isEmpty())
            <section class="atlas-path-empty" aria-label="No courses"><h2>No courses</h2><p>No courses are available yet.</p></section>
        @else
            <nav class="atlas-path-courses" aria-label="Courses">
                @foreach ($tree as $courseNode)
                    <a href="#course-{{ $courseNode['course']->id }}" @if ($course?->id === $courseNode['course']->id) aria-current="location" @endif>{{ $courseNode['course']->name }}</a>
                @endforeach
            </nav>
            <p class="atlas-path-note atlas-path-navigation-note">Mission order is a guide. Available challenges can be opened now.</p>
        @endif

        @foreach ($tree as $courseNode)
            @php
                $pathCourse = $courseNode['course'];
                $courseLocked = ! $courseNode['accessible'];
                $isCurrentCourse = $course?->id === $pathCourse->id && ! $courseLocked;
                $boss = $courseNode['boss'];
            @endphp
            <article id="course-{{ $pathCourse->id }}" class="atlas-path-course" aria-labelledby="course-{{ $pathCourse->id }}-title">
                <details class="atlas-path-course-details" open>
                    <summary class="atlas-path-course-heading">
                        <div class="atlas-path-course-title">
                            <p class="atlas-path-meta">Course {{ str_pad((string) $pathCourse->order_num, 2, '0', STR_PAD_LEFT) }} · {{ $courseLocked ? 'Locked' : ($boss['state'] === 'PASSED' ? 'Complete' : ($isCurrentCourse ? 'Current' : 'Available')) }}</p>
                            <h2 id="course-{{ $pathCourse->id }}-title">{{ $pathCourse->name }}</h2>
                            @if ($pathCourse->description)<p class="atlas-path-description">{{ $pathCourse->description }}</p>@endif
                        </div>
                        <div class="atlas-path-course-progress">
                            <p class="atlas-path-meta">Course position</p>
                            <p class="atlas-path-meta">{{ $courseNode['progress']['completed'] }} / {{ $courseNode['progress']['total'] }} challenges complete · {{ $courseNode['progress']['percent'] }}%</p>
                            @if ($courseNode['progress']['total'] > 0)
                                <div class="progress" role="progressbar" aria-label="{{ $pathCourse->name }} progress" aria-valuemin="0" aria-valuemax="{{ $courseNode['progress']['total'] }}" aria-valuenow="{{ $courseNode['progress']['completed'] }}" aria-valuetext="{{ $courseNode['progress']['completed'] }} of {{ $courseNode['progress']['total'] }} challenges complete"><span style="width: {{ $courseNode['progress']['percent'] }}%"></span></div>
                            @endif
                        </div>
                    </summary>
                    @if ($courseLocked)<p class="atlas-path-lock-reason">{{ $courseNode['locked_reason'] }}</p>@endif
                    <div class="atlas-path-route">
                        <ol class="atlas-path-sections" aria-label="{{ $pathCourse->name }} sections">
                            @foreach ($courseNode['sections'] as $sectionNode)
                                @php
                                    $pathSection = $sectionNode['section'];
                                    $sectionTitle = $pathSection?->title ?? 'Additional challenges';
                                    $sectionKey = $pathSection?->id ?? 'additional-'.$pathCourse->id;
                                    $sectionCurrent = ! $courseLocked && $nextMission !== null && $sectionNode['missions']->contains(fn ($row) => $row['mission']->id === $nextMission->id);
                                    $sectionComplete = $sectionNode['progress']['total'] > 0 && $sectionNode['progress']['completed'] === $sectionNode['progress']['total'];
                                @endphp
                                <li id="section-{{ $sectionKey }}" class="atlas-path-section">
                                    <details @if ($sectionCurrent) open @endif>
                                        <summary class="atlas-path-checkpoint">
                                            <span class="atlas-path-marker {{ $sectionCurrent ? 'is-current' : ($sectionComplete ? 'is-complete' : '') }}" aria-hidden="true">@if ($sectionComplete)<svg viewBox="0 0 24 24"><path d="m5 12 4 4 10-10" /></svg>@endif</span>
                                            <h3>{{ $sectionTitle }}</h3>
                                            <p class="atlas-path-meta">{{ $sectionNode['progress']['completed'] }} / {{ $sectionNode['progress']['total'] }} challenges complete @if ($sectionComplete) · Complete @elseif ($sectionCurrent) · Current @elseif ($courseLocked) · Locked @endif</p>
                                        </summary>
                                        <ol class="atlas-path-missions" aria-label="{{ $sectionTitle }} challenges">
                                            @foreach ($sectionNode['missions'] as $row)
                                                @php
                                                    $mission = $row['mission'];
                                                    $isCurrent = ! $courseLocked && $nextMission !== null && $nextMission->id === $mission->id;
                                                    $state = $courseLocked ? 'Locked' : ($row['state'] === 'COMPLETED' ? 'Completed' : ($isCurrent ? 'Current' : 'Available'));
                                                @endphp
                                                <li class="atlas-path-mission" data-state="{{ strtolower($state) }}" data-domain-state="{{ $row['state'] }}" @if ($isCurrent) aria-current="step" @endif>
                                                    <span class="atlas-path-marker" aria-hidden="true">
                                                        @if ($state === 'Completed')<svg viewBox="0 0 24 24"><path d="m5 12 4 4 10-10" /></svg>@elseif ($state === 'Locked')<svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="1"/><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>@endif
                                                    </span>
                                                    <div class="atlas-path-mission-content">
                                                        <p class="atlas-path-meta">{{ $state }}@if ($isCurrent) · You are here @endif</p>
                                                        <h4>@foreach (explode('_', $mission->title) as $part)@unless ($loop->first)_<wbr>@endunless{{ $part }}@endforeach</h4>
                                                        @if ($state === 'Locked')
                                                            <p class="atlas-path-note">{{ $courseNode['locked_reason'] }}</p>
                                                            @if ($row['state'] === 'COMPLETED')<p class="atlas-path-note">Completed work remains recorded.</p>@endif
                                                        @elseif ($isCurrent)
                                                            <p class="atlas-path-note">{{ $row['state'] === 'IN PROGRESS' ? 'Saved draft detected. Resume this lesson.' : 'Open the lesson, then work through the coding challenge.' }}</p>
                                                            <div class="atlas-path-actions"><a href="{{ route('mission.show', $mission) }}" class="btn btn-primary">Continue →</a> <span class="atlas-path-meta">{{ $mission->difficulty }} · +{{ $mission->points }} XP</span></div>
                                                        @else
                                                            <div class="atlas-path-actions"><a href="{{ route('mission.show', $mission) }}" class="atlas-path-link" aria-label="{{ $state === 'Completed' ? 'Review' : 'Open lesson' }}: {{ $mission->title }}">{{ $state === 'Completed' ? 'Review' : 'Open lesson' }} →</a> <span class="atlas-path-meta">{{ $mission->difficulty }} · +{{ $mission->points }} XP</span></div>
                                                        @endif
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </details>
                                </li>
                            @endforeach
                        </ol>
                        <section class="atlas-path-boss" aria-labelledby="boss-{{ $pathCourse->id }}-title">
                            <span class="atlas-path-marker" aria-hidden="true"></span>
                            <div class="atlas-path-boss-labels">
                                <p class="atlas-path-meta">Final course milestone</p>
                                @if ($boss['state'] === 'PASSED')
                                    <span class="atlas-stamp atlas-stamp--grn">Passed</span>
                                @elseif ($boss['state'] === 'AVAILABLE')
                                    <span class="atlas-stamp atlas-stamp--ink">Available</span>
                                @elseif ($boss['assessment'] !== null)
                                    <span class="atlas-stamp atlas-stamp--ink">Locked</span>
                                @endif
                            </div>
                            <h3 id="boss-{{ $pathCourse->id }}-title">{{ $boss['assessment']?->title ?? 'Boss Challenge' }}</h3>
                            <p class="atlas-path-note">{{ $boss['reason'] }}</p>
                            @if ($boss['assessment'] !== null && $boss['accessible'])
                                <a href="{{ route('assessment.show', $boss['assessment']) }}" class="btn btn-secondary">{{ $boss['state'] === 'PASSED' ? 'Review challenge' : 'Start challenge' }} →</a>
                            @endif
                        </section>
                    </div>
                </details>
            </article>
        @endforeach
        @if ($recommendations->isNotEmpty())
            <section class="atlas-path-recommendations" aria-labelledby="recommendations-heading">
                <h2 id="recommendations-heading">Recommended next actions</h2>
                <x-recommendation-cards :recommendations="$recommendations" variant="student" />
            </section>
        @endif
    </div>
@endsection
