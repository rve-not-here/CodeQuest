@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@php
    $mission = $resume !== null && $resume['type'] === 'mission' ? $resume['mission'] : null;
    $section = $resume['section'] ?? null;
    $competencyState = $competencySummary === null
        ? 'NO EVIDENCE YET'
        : strtoupper(str_replace('_', ' ', $competencySummary['state']));
@endphp

@section('title', 'Dashboard')

@section('content')
    <div class="atlas-dashboard">
        <header class="atlas-dashboard-heading">
            <h1>Current Objective</h1>
            <a class="atlas-dashboard-xp" href="{{ route('xp-ledger') }}"><span>XP BALANCE</span> <strong>{{ number_format($totalXp) }}</strong><span class="sr-only">. View XP ledger</span></a>
        </header>

        <div class="atlas-dashboard-learning">
            <section class="atlas-dashboard-objective" aria-labelledby="objective-title">
                <span class="sort-code">OBJ-{{ strtoupper(substr(sha1(($mission ?? $course)?->id ?? 'none'), 0, 4)) }}</span>
                @if ($course !== null)
                    <p class="atlas-dashboard-context">{{ $course->name }}@if ($section !== null)<span aria-hidden="true"> / </span>{{ $section->title }}@endif</p>
                @endif
                <p class="atlas-dashboard-state">{{ ! $hasLearnableCourses ? 'Not started' : ($course === null ? 'Complete' : ($mission !== null ? ($resumeHasDraft ? 'Current · Draft saved' : 'Current challenge') : 'Course milestone')) }}</p>
                @if (! $hasLearnableCourses)
                    <h2 id="objective-title">No lessons available yet</h2>
                    <p class="atlas-dashboard-description">There are no active courses with challenges. Your learning path will appear here when lessons are available.</p>
                @elseif ($course === null)
                    <h2 id="objective-title">Course complete</h2>
                    <p class="atlas-dashboard-description">You have cleared every active course. Review your learning record.</p>
                @elseif ($mission !== null)
                    <h2 id="objective-title">@foreach (explode('_', $mission->title) as $part)@unless ($loop->first)_<wbr>@endunless{{ $part }}@endforeach</h2>
                    <p class="atlas-dashboard-description">{{ $resumeHasDraft ? 'Saved work detected. Your draft is ready to continue.' : 'Open the lesson, then work through the coding challenge.' }}</p>
                    <dl class="atlas-dashboard-mission-meta">
                        <div><dt class="sr-only">Type</dt><dd>Coding challenge</dd></div>
                        <div><dt class="sr-only">Difficulty</dt><dd>{{ ucfirst($mission->difficulty) }}</dd></div>
                        <div><dt class="sr-only">Completion reward</dt><dd class="font-mono">+{{ $mission->points }} XP</dd></div>
                    </dl>
                @else
                    <h2 id="objective-title">Boss Challenge ready</h2>
                    <p class="atlas-dashboard-description">Return to the Learning Path to inspect the course milestone.</p>
                @endif
                <div class="atlas-dashboard-actions">
                    @if ($mission !== null)
                        <a href="{{ route($resumeHasDraft ? 'mission.challenge' : 'mission.show', $mission) }}" class="btn btn-primary btn-lg">{{ $resumeHasDraft ? 'Resume Challenge' : 'Continue Learning' }} →</a>
                    @else
                        <a href="{{ route('learning-path') }}" class="btn btn-primary btn-lg">{{ $course === null ? 'View Learning Path' : 'Continue Learning' }} →</a>
                    @endif
                </div>
            </section>

            <section class="atlas-dashboard-position" aria-labelledby="position-heading">
                <div class="flex items-center gap-3">
                    <h2 id="position-heading">Course position</h2>
                    <span class="sort-code">POS-{{ strtoupper(substr(sha1($course?->id ?? 'none'), 0, 4)) }}</span>
                </div>
                @if ($courseProgress !== null)
                    <div class="atlas-dashboard-track">
                        <div class="atlas-dashboard-track-stop">
                            <p class="atlas-dashboard-state">Completed challenges</p>
                            <p class="atlas-dashboard-count"><strong>{{ $courseProgress['completed'] }}</strong> / {{ $courseProgress['total'] }}</p>
                            <div class="progress" role="progressbar" aria-label="{{ $course->name }} progress" aria-valuemin="0" aria-valuemax="{{ $courseProgress['total'] }}" aria-valuenow="{{ $courseProgress['completed'] }}" aria-valuetext="{{ $courseProgress['completed'] }} of {{ $courseProgress['total'] }} challenges complete"><span style="width: {{ $courseProgress['percent'] }}%"></span></div>
                        </div>
                        <div class="atlas-dashboard-track-stop atlas-dashboard-track-current">
                            <p class="atlas-dashboard-state">{{ $mission !== null ? 'Current section' : 'Course milestone' }}</p>
                            <p>{{ $section?->title ?? ($mission !== null ? 'Next available challenge' : 'Boss Challenge') }}</p>
                            <p class="atlas-dashboard-track-note">{{ $course->name }}</p>
                        </div>
                    </div>
                    <a href="{{ route('learning-path') }}" class="atlas-dashboard-link">View Learning Path →</a>
                @else
                    <p class="atlas-dashboard-position-empty">{{ $hasLearnableCourses ? 'All active courses complete' : 'No active lessons' }}</p>
                    <p class="atlas-dashboard-track-note">{{ $hasLearnableCourses ? 'Your completed work remains in your learning record.' : 'Check back when your courses are ready.' }}</p>
                @endif
            </section>
        </div>

        <div class="atlas-dashboard-record">
            <section class="atlas-dashboard-evidence" aria-labelledby="evidence-heading">
                <div class="atlas-dashboard-section-heading">
                    <div class="flex items-center gap-3">
                        <h2 id="evidence-heading">Recent learning evidence</h2>
                        <span class="sort-code">EVD-{{ strtoupper(substr(sha1('evidence'), 0, 4)) }}</span>
                    </div>
                    <a href="{{ route('timeline') }}" class="atlas-dashboard-link">View timeline →</a>
                </div>
                @if ($recentActivity->isNotEmpty())
                    <ol class="atlas-dashboard-events">
                        @foreach ($recentActivity as $activity)
                            <li><p>{{ $activity->message }}</p><time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->format('M d · H:i') }}</time></li>
                        @endforeach
                    </ol>
                @else
                    <p class="atlas-dashboard-no-evidence">No learning activity recorded yet.</p>
                @endif
                <p class="atlas-dashboard-history"><span>{{ $learnerStats['boss_challenges_passed'] }} {{ $learnerStats['boss_challenges_passed'] === 1 ? 'Boss Challenge' : 'Boss Challenges' }} passed</span><a href="{{ route('achievements') }}">{{ $learnerStats['achievements'] }} {{ $learnerStats['achievements'] === 1 ? 'achievement' : 'achievements' }} →</a></p>
            </section>
            <section class="atlas-dashboard-reflection" aria-labelledby="reflection-heading">
                <div class="flex items-center gap-3">
                    <h2 id="reflection-heading">Learning performance</h2>
                    <span class="sort-code">LPR-{{ strtoupper(substr(sha1($competencySummary['name'] ?? 'none'), 0, 4)) }}</span>
                </div>
                <p>{{ $competencySummary['name'] ?? 'No active skill area' }}</p>
                <p class="atlas-dashboard-state">{{ $competencyState }}</p>
                <a href="{{ route('competency') }}" class="atlas-dashboard-link">View competency evidence →</a>
                <div class="atlas-dashboard-recommendation">
                    <h3>Where to focus</h3>
                    <a href="{{ route('recommendations') }}" class="atlas-dashboard-link">View recommendations →</a>
                </div>
            </section>
        </div>
    </div>
@endsection
