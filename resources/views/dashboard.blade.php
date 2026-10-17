@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@php
    $mission = $resume !== null && $resume['type'] === 'mission' ? $resume['mission'] : null;
    $section = $resume['section'] ?? null;
    $competencyState = $competencySummary === null
        ? null
        : ucfirst(str_replace('_', ' ', $competencySummary['state']));
    $position = $mission !== null && $courseProgress !== null
        ? 'Challenge '.$mission->order_num.' of '.$courseProgress['total']
        : null;
@endphp

@section('title', 'Dashboard')

@section('content')
    <div class="atlas-dashboard">
        <div class="atlas-dashboard-band">
            <h1>Current Objective</h1>
            <div class="atlas-dashboard-band-side">
                @if ($course !== null)
                    <p class="atlas-dashboard-crumb">{{ $course->name }}</p>
                @endif
                <a href="{{ route('learning-path') }}" class="atlas-dashboard-link">View Learning Path →</a>
            </div>
        </div>

        <dl class="atlas-dashboard-wall" aria-label="Standing record">
            <div class="atlas-dashboard-figure">
                <dt>XP balance</dt>
                <dd class="atlas-dashboard-fig"><a class="atlas-dashboard-fig-link" href="{{ route('xp-ledger') }}" aria-label="XP balance {{ number_format($totalXp) }}. Open XP ledger">{{ number_format($totalXp) }}</a><span class="atlas-dashboard-fig-unit">XP</span></dd>
            </div>
            <div class="atlas-dashboard-figure">
                <dt>Completed challenges</dt>
                @if ($courseProgress !== null)
                    <dd class="atlas-dashboard-fig"><span>{{ $courseProgress['completed'] }}</span><span class="atlas-dashboard-fig-muted"> / {{ $courseProgress['total'] }}</span></dd>
                    <div class="progress" role="progressbar" aria-label="{{ $course->name }} progress" aria-valuemin="0" aria-valuemax="{{ $courseProgress['total'] }}" aria-valuenow="{{ $courseProgress['completed'] }}" aria-valuetext="{{ $courseProgress['completed'] }} of {{ $courseProgress['total'] }} challenges complete"><span style="width: {{ $courseProgress['percent'] }}%"></span></div>
                @else
                    <dd class="atlas-dashboard-figure-note">{{ $hasLearnableCourses ? 'All active courses complete' : 'No active lessons' }}</dd>
                    @if ($hasLearnableCourses)
                        <p class="atlas-dashboard-figure-note">Your completed work remains in your learning record.</p>
                    @else
                        <p class="atlas-dashboard-figure-note">Check back when your courses are ready.</p>
                    @endif
                @endif
            </div>
            <div class="atlas-dashboard-figure">
                <dt>Section</dt>
                <dd class="atlas-dashboard-figure-text">{{ $section?->title ?? $course?->name ?? '—' }}</dd>
            </div>
            <div class="atlas-dashboard-figure">
                <dt>Competency</dt>
                <dd class="atlas-dashboard-figure-text"><a class="atlas-dashboard-fig-link-text" href="{{ route('competency') }}" aria-label="Competency {{ $competencySummary['name'] ?? 'No active skill area' }}. View competency evidence">{{ $competencySummary['name'] ?? 'No active skill area' }}@if ($competencyState !== null) · {{ $competencyState }}@endif</a></dd>
            </div>
            <div class="atlas-dashboard-figure">
                <dt>Boss Challenges passed</dt>
                <dd class="atlas-dashboard-fig">{{ $learnerStats['boss_challenges_passed'] }}</dd>
            </div>
            <div class="atlas-dashboard-figure">
                <dt>Achievements</dt>
                <dd class="atlas-dashboard-fig"><a class="atlas-dashboard-fig-link" href="{{ route('achievements') }}" aria-label="{{ $learnerStats['achievements'] }} {{ $learnerStats['achievements'] === 1 ? 'achievement' : 'achievements' }}. Open achievements">{{ $learnerStats['achievements'] }}</a></dd>
            </div>
        </dl>

        <section class="atlas-dashboard-objective" aria-labelledby="objective-title">
            <div class="atlas-dashboard-objective-copy">
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
                        <div><dt>Type</dt><dd>Coding challenge</dd></div>
                        <div><dt>Difficulty</dt><dd>{{ ucfirst($mission->difficulty) }}</dd></div>
                        <div><dt>Completion reward</dt><dd class="font-mono">+{{ $mission->points }} XP</dd></div>
                        <div><dt>Position</dt><dd class="font-mono">{{ $position ?? 'Challenge '.$mission->order_num.' of course' }}</dd></div>
                    </dl>
                @else
                    <h2 id="objective-title">Boss Challenge ready</h2>
                    <p class="atlas-dashboard-description">Return to the Learning Path to inspect the course milestone.</p>
                @endif
            </div>
            <div class="atlas-dashboard-actions">
                @if ($mission !== null)
                    <a href="{{ route($resumeHasDraft ? 'mission.challenge' : 'mission.show', $mission) }}" class="btn btn-primary btn-lg">{{ $resumeHasDraft ? 'Resume Challenge' : 'Continue Learning' }} →</a>
                @else
                    <a href="{{ route('learning-path') }}" class="btn btn-primary btn-lg">{{ $course === null ? 'View Learning Path' : 'Continue Learning' }} →</a>
                @endif
            </div>
        </section>

        <section class="atlas-dashboard-evidence" aria-labelledby="evidence-heading">
            <div class="atlas-dashboard-section-heading">
                <h2 id="evidence-heading">Recent learning evidence</h2>
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
        </section>

        <section class="atlas-dashboard-focus" aria-labelledby="focus-heading">
            <h2 id="focus-heading">Where to focus</h2>
            <a href="{{ route('recommendations') }}" class="atlas-dashboard-link">View recommendations →</a>
        </section>
    </div>
@endsection
