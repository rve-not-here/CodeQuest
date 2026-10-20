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
    $sheetNumber = $course !== null && $course->order_num > 0
        ? str_pad((string) $course->order_num, 3, '0', STR_PAD_LEFT)
        : null;
    $entryNumber = $mission !== null ? str_pad((string) $mission->order_num, 3, '0', STR_PAD_LEFT) : null;
    $entryStamp = match (true) {
        ! $hasLearnableCourses => ['word' => 'Not started', 'tone' => 'ink'],
        $course === null => ['word' => 'Complete', 'tone' => 'grn'],
        $mission !== null && $resumeHasDraft => ['word' => 'Draft saved', 'tone' => 'vio'],
        default => null,
    };

    // Record stamps follow the log grammar: pass green, grading violet, warnings
    // amber, everything else ink.
    $stampFor = fn ($activity): array => match ($activity->type) {
        'mission_completed' => ['word' => 'Completed', 'tone' => 'grn'],
        'knowledge_check_completed' => ['word' => 'Graded', 'tone' => 'vio'],
        'wrong_submission' => ['word' => 'Revise', 'tone' => 'amb'],
        'grading_unavailable' => ['word' => 'Pending', 'tone' => 'amb'],
        default => ['word' => 'Logged', 'tone' => 'ink'],
    };
    // The controller already drops non-learning rows (logins), so the newest
    // record is the mounted specimen and the next three become ruled rows.
    $specimen = $recentActivity->first();
    $evidenceRows = $recentActivity
        ->reject(fn ($activity): bool => $specimen !== null && $activity->is($specimen))
        ->take(3);
@endphp

@section('title', 'Dashboard')

@section('content')
    <div class="atlas-dashboard">
        <div class="log-running">
            <span>{{ $course !== null ? 'Log sheet'.($sheetNumber !== null ? ' '.$sheetNumber : '').' · '.$course->name : 'Learning log' }}</span>
            <a href="{{ route('learning-path') }}">Learning path →</a>
        </div>

        <dl class="atlas-dashboard-wall" aria-label="Standing record">
            <div class="atlas-dashboard-figure">
                <dt>XP balance</dt>
                <dd class="atlas-dashboard-fig"><a class="atlas-dashboard-fig-link" href="{{ route('xp-ledger') }}" aria-label="XP balance {{ number_format($totalXp) }}. Open XP ledger">{{ number_format($totalXp) }}</a><span class="atlas-dashboard-fig-unit">XP</span></dd>
                @if ($totalXp === 0)
                    <p class="atlas-dashboard-figure-note">No XP yet — complete a challenge to start.</p>
                @endif
            </div>
            <div class="atlas-dashboard-figure">
                <dt>Completed challenges</dt>
                @if ($courseProgress !== null)
                    <dd class="atlas-dashboard-fig"><a class="atlas-dashboard-fig-link" href="{{ route('progress') }}" aria-label="{{ $courseProgress['completed'] }} of {{ $courseProgress['total'] }} challenges complete. Open the course record">{{ $courseProgress['completed'] }}</a><span class="atlas-dashboard-fig-muted"> / {{ $courseProgress['total'] }}</span></dd>
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
                @if ($section !== null)
                    <dd class="atlas-dashboard-figure-text"><a class="atlas-dashboard-fig-link-text" href="{{ route('section-progress') }}" aria-label="Section {{ $section->title }}. Open the section record">{{ $section->title }}</a></dd>
                @else
                    <dd class="atlas-dashboard-figure-text">{{ $course?->name ?? '—' }}</dd>
                @endif
            </div>
            <div class="atlas-dashboard-figure">
                <dt>Competency</dt>
                <dd class="atlas-dashboard-figure-text"><a class="atlas-dashboard-fig-link-text" href="{{ route('competency') }}" aria-label="Competency {{ $competencySummary['name'] ?? 'No active skill area' }}. View competency evidence">{{ $competencySummary['name'] ?? 'No active skill area' }}@if ($competencyState !== null) · {{ $competencyState }}@endif</a></dd>
            </div>
            <div class="atlas-dashboard-figure">
                <dt>Boss Challenges passed</dt>
                <dd class="atlas-dashboard-fig"><a class="atlas-dashboard-fig-link" href="{{ route('assessments') }}" aria-label="{{ $learnerStats['boss_challenges_passed'] }} Boss Challenges passed. Open Boss Challenges">{{ $learnerStats['boss_challenges_passed'] }}</a></dd>
            </div>
            <div class="atlas-dashboard-figure">
                <dt>Achievements</dt>
                <dd class="atlas-dashboard-fig"><a class="atlas-dashboard-fig-link" href="{{ route('achievements') }}" aria-label="{{ $learnerStats['achievements'] }} {{ $learnerStats['achievements'] === 1 ? 'achievement' : 'achievements' }}. Open achievements">{{ $learnerStats['achievements'] }}</a></dd>
            </div>
        </dl>

        <section class="atlas-dashboard-objective" aria-labelledby="objective-title">
            @if ($entryNumber !== null)
                <span class="atlas-dashboard-goal" aria-hidden="true">{{ $entryNumber }}</span>
            @endif
            <div class="atlas-dashboard-objective-copy">
                <div class="atlas-dashboard-entry">
                    <span>{{ $entryNumber !== null ? 'Entry '.$entryNumber.' · ' : '' }}Current Objective</span>
                    @if ($entryStamp !== null)
                        <span class="atlas-stamp atlas-stamp--{{ $entryStamp['tone'] }}">@if ($stampIcon = \App\Support\StampIcon::for($entryStamp['word']))<x-icon :name="$stampIcon" size="12" />@endif{{ $entryStamp['word'] }}</span>
                    @endif
                </div>
                @if (! $hasLearnableCourses)
                    <h1 id="objective-title">No lessons available yet</h1>
                    <p class="atlas-dashboard-description">There are no active courses with challenges. Your learning path will appear here when lessons are available.</p>
                @elseif ($course === null)
                    <h1 id="objective-title">Course complete</h1>
                    <p class="atlas-dashboard-description">You have cleared every active course. Review your learning record.</p>
                @elseif ($mission !== null)
                    <h1 id="objective-title">@foreach (explode('_', $mission->title) as $part)@unless ($loop->first)_<wbr>@endunless{{ $part }}@endforeach</h1>
                    <p class="atlas-dashboard-description">{{ $resumeHasDraft ? 'Saved work detected. Your draft is ready to continue.' : 'Open the lesson, then work through the coding challenge.' }}</p>
                    <dl class="atlas-dashboard-mission-meta">
                        <div><dt>Type</dt><dd>Coding challenge</dd></div>
                        <div><dt>Difficulty</dt><dd>{{ ucfirst($mission->difficulty) }}</dd></div>
                        <div><dt>Completion reward</dt><dd class="font-mono">+{{ $mission->points }} XP</dd></div>
                        <div><dt>Position</dt><dd class="font-mono">{{ $position ?? 'Challenge '.$mission->order_num.' of course' }}</dd></div>
                    </dl>
                @else
                    <h1 id="objective-title">Boss Challenge ready</h1>
                    <p class="atlas-dashboard-description">Return to the Learning Path to inspect the course milestone.</p>
                @endif
            </div>
            <div class="atlas-dashboard-actions">
                @if ($mission !== null)
                    <a href="{{ route($resumeHasDraft ? 'mission.challenge' : 'mission.show', $mission) }}" class="btn btn-primary btn-lg">{{ $resumeHasDraft ? 'Resume challenge' : 'Continue learning' }} →</a>
                @else
                    <a href="{{ route('learning-path') }}" class="btn btn-primary btn-lg">{{ $course === null ? 'View learning path' : 'Continue learning' }} →</a>
                @endif
            </div>
        </section>

        <section class="atlas-dashboard-evidence" aria-labelledby="evidence-heading">
            <div class="atlas-dashboard-section-heading">
                <h2 id="evidence-heading">Recent learning evidence</h2>
                <a href="{{ route('timeline') }}" class="atlas-dashboard-link">View timeline →</a>
            </div>
            @if ($specimen !== null)
                @php $specimenStamp = $stampFor($specimen); @endphp
                <div class="atlas-evidence-specimen">
                    <div class="atlas-sheet" aria-hidden="true">
                        <span class="atlas-tape atlas-tape--l"></span>
                        <span class="atlas-tape atlas-tape--r"></span>
                        <span class="atlas-sheet-rule" style="width: 100%"></span>
                        <span class="atlas-sheet-rule" style="width: 62%"></span>
                        <span class="atlas-sheet-rule" style="width: 84%"></span>
                        <span class="atlas-sheet-rule" style="width: 44%"></span>
                        <span class="atlas-sheet-rule" style="width: 70%"></span>
                        <span class="atlas-sheet-caret"></span>
                    </div>
                    <div class="atlas-evidence-copy">
                        <p class="atlas-evidence-title">{{ $specimen->message }}</p>
                        <p class="atlas-evidence-meta">@if ($specimen->pts > 0)+{{ $specimen->pts }} XP · @endif{{ $specimen->created_at->format('M d, H:i') }}</p>
                    </div>
                    <span class="atlas-stamp atlas-stamp--{{ $specimenStamp['tone'] }}">@if ($stampIcon = \App\Support\StampIcon::for($specimenStamp['word']))<x-icon :name="$stampIcon" size="12" />@endif{{ $specimenStamp['word'] }}</span>
                </div>
                @if ($evidenceRows->isNotEmpty())
                    <ol class="atlas-dashboard-events">
                        @foreach ($evidenceRows as $activity)
                            @php $stamp = $stampFor($activity); @endphp
                            <li>
                                <time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->format('M d · H:i') }}</time>
                                <p>{{ $activity->message }}</p>
                                <span class="atlas-stamp atlas-stamp--{{ $stamp['tone'] }}">@if ($stampIcon = \App\Support\StampIcon::for($stamp['word']))<x-icon :name="$stampIcon" size="12" />@endif{{ $stamp['word'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            @else
                <p class="atlas-dashboard-no-evidence">No learning activity recorded yet.</p>
            @endif
        </section>

        <section class="atlas-dashboard-focus" aria-labelledby="focus-heading">
            <div class="atlas-dashboard-focus-copy">
                <h2 id="focus-heading">Where to focus</h2>
                @if ($recommendations->isNotEmpty())
                    @php $focus = $recommendations->first(); @endphp
                    <p class="atlas-dashboard-focus-title">{{ $focus['title'] }}</p>
                    <p class="atlas-dashboard-focus-subtitle">{{ $focus['subtitle'] }}</p>
                @else
                    <p class="atlas-dashboard-focus-title">You're on track</p>
                    <p class="atlas-dashboard-focus-subtitle">No priority actions right now. Keep going.</p>
                @endif
            </div>
            @php
                $focus = $recommendations->first();
                $focusIsAssessment = $focus !== null && str_contains($focus['href'], '/assessments/');
                $focusHref = $focusIsAssessment ? route('recommendations') : ($focus['href'] ?? route('recommendations'));
                $focusCta = $focusIsAssessment ? 'View recommendations' : ($focus['cta'] ?? 'View recommendations');
            @endphp
            <a href="{{ $focusHref }}" class="btn btn-secondary">{{ $focusCta }}</a>
        </section>
    </div>
@endsection
