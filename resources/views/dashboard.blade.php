@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@php
    $mission = $resume !== null && $resume['type'] === 'mission' ? $resume['mission'] : null;
    $section = $resume['section'] ?? null;
    $position = $mission !== null && $courseProgress !== null
        ? 'Challenge '.$mission->order_num.' of '.$courseProgress['total']
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
        <section class="atlas-dashboard-objective" aria-labelledby="objective-title">
            <header class="atlas-dashboard-objective-head">
                <div class="atlas-dashboard-entry">
                    @if ($entryNumber !== null)
                        <span>Entry <span class="atlas-dashboard-entry-number">{{ $entryNumber }}</span></span>
                        <span class="atlas-dashboard-entry-divider" aria-hidden="true">/</span>
                    @endif
                    <span>Current Objective</span>
                </div>
                @if ($entryStamp !== null)
                    <span class="atlas-stamp atlas-stamp--{{ $entryStamp['tone'] }}">@if ($stampIcon = \App\Support\StampIcon::for($entryStamp['word']))<x-icon :name="$stampIcon" size="12" />@endif{{ $entryStamp['word'] }}</span>
                @endif
                <button type="button" class="atlas-dashboard-help" data-dialog-open="how-codequest-works">How it works</button>
            </header>

            <div class="atlas-dashboard-objective-copy">
                @if ($course !== null)
                    <p class="atlas-dashboard-course">
                        {{ $course->name }}@if ($section !== null) <span class="atlas-dashboard-course-divider" aria-hidden="true">/</span> Section {{ str_pad((string) $section->order_num, 2, '0', STR_PAD_LEFT) }} · {{ $section->title }}@endif
                    </p>
                @endif
                @if (! $hasLearnableCourses)
                    <h1 id="objective-title">No lessons available yet</h1>
                    <p class="atlas-dashboard-description">There are no active courses with challenges. Your learning path will appear here when lessons are available.</p>
                    <p class="game-copy-line">{{ \App\Support\GameCopy::line('empty.courses') }}</p>
                @elseif ($course === null)
                    <h1 id="objective-title">Course complete</h1>
                    <p class="atlas-dashboard-description">You have cleared every active course.@if ($focusCard !== null && $focusCard['slot'] === 4) Your next course is ready below.@else Review your learning record.@endif</p>
                @elseif ($mission !== null)
                    <h1 id="objective-title">@foreach (explode('_', $mission->title) as $part)@unless ($loop->first)_<wbr>@endunless{{ $part }}@endforeach</h1>
                    <p class="atlas-dashboard-description">{{ $resumeHasDraft ? 'Saved work detected. Your draft is ready to continue.' : 'Open the lesson, then work through the coding challenge.' }}</p>
                    <dl class="atlas-dashboard-chips">
                        <div class="atlas-dashboard-chip"><dt>Type</dt><dd>Coding challenge</dd></div>
                        <div class="atlas-dashboard-chip"><dt>Difficulty</dt><dd>{{ ucfirst($mission->difficulty) }}</dd></div>
                    </dl>
                @else
                    <h1 id="objective-title">Boss Challenge ready</h1>
                    <p class="atlas-dashboard-description">Return to the Learning Path to inspect the course milestone.</p>
                @endif
            </div>

            <aside class="atlas-dashboard-order" aria-label="Next action">
                <div class="atlas-dashboard-order-head">
                    <p class="atlas-dashboard-order-kicker">{{ $mission !== null && $resumeHasDraft ? 'Resume entry' : 'Next action' }}</p>
                    @if ($mission !== null && $courseProgress !== null && $courseProgress['total'] > 0)
                        <p class="atlas-dashboard-order-logged">
                            <span>Challenges logged</span>
                            <span class="font-mono">{{ str_pad((string) $courseProgress['completed'], 2, '0', STR_PAD_LEFT) }} / {{ str_pad((string) $courseProgress['total'], 2, '0', STR_PAD_LEFT) }}</span>
                        </p>
                    @endif
                </div>

                @if ($mission !== null && $courseProgress !== null && $courseProgress['total'] > 0)
                    <div class="atlas-dashboard-order-position" aria-hidden="true">
                        <span class="atlas-dashboard-position-number" data-countup="{{ $mission->order_num }}" data-countup-format="pad2">{{ str_pad((string) $mission->order_num, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="atlas-dashboard-position-total">/ {{ str_pad((string) $courseProgress['total'], 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="atlas-dashboard-progress" aria-hidden="true">
                        @foreach (range(1, $courseProgress['total']) as $step)
                            <span @class([
                                'atlas-dashboard-progress-step',
                                'is-complete' => $step <= $courseProgress['completed'],
                                'is-current' => $step === $courseProgress['completed'] + 1,
                            ])></span>
                        @endforeach
                    </div>
                    <p class="atlas-dashboard-order-caption">{{ $position }}</p>
                @elseif ($mission !== null)
                    <p class="atlas-dashboard-order-caption">Challenge {{ $mission->order_num }} of course</p>
                @endif

                @if ($mission !== null)
                    <dl class="atlas-dashboard-order-figures">
                        <div><dt>Completion reward</dt><dd class="font-mono">+{{ $mission->points }} XP</dd></div>
                    </dl>
                @elseif (! $hasLearnableCourses)
                    <p class="atlas-dashboard-order-note">No active lessons are open yet.</p>
                @elseif ($course === null)
                    <p class="atlas-dashboard-order-note">All active courses are cleared.</p>
                @else
                    <p class="atlas-dashboard-order-note">The course milestone is next.</p>
                @endif

                <div class="atlas-dashboard-actions">
                    @if ($mission !== null)
                        <a href="{{ route($resumeHasDraft ? 'mission.challenge' : 'mission.show', $mission) }}" class="btn btn-primary btn-lg">{{ $resumeHasDraft ? 'Resume challenge' : 'Continue learning' }} →</a>
                    @else
                        <a href="{{ route('learning-path') }}" class="btn btn-primary btn-lg">{{ $course === null ? 'View learning path' : 'Continue learning' }} →</a>
                    @endif
                </div>
            </aside>
        </section>

        <section class="atlas-dashboard-evidence" aria-labelledby="evidence-heading">
            <div class="atlas-dashboard-section-heading">
                <h2 id="evidence-heading">Recent learning evidence</h2>
                <a href="{{ route('timeline') }}" class="atlas-dashboard-link">Open learning timeline <x-icon name="arrow-right" data-arrow size="14" /></a>
            </div>
            @if ($specimen !== null)
                @php $specimenStamp = $stampFor($specimen); @endphp
                <div class="atlas-evidence-specimen">
                    <div class="atlas-sheet" aria-hidden="true">
                        <span class="atlas-sheet-caption">Record #{{ $specimen->id }}</span>
                        <span class="atlas-sheet-rule" style="width: 100%"></span>
                        <span class="atlas-sheet-rule" style="width: 74%"></span>
                        <span class="atlas-sheet-rule" style="width: 52%"></span>
                        <span class="atlas-sheet-tail">
                            <span class="atlas-sheet-rule" style="width: 70%"></span>
                            <span class="atlas-sheet-caret"></span>
                        </span>
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
                                <time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->format('M d, H:i') }}</time>
                                <p>{{ $activity->message }}@if ($activity->pts > 0)<span class="atlas-dashboard-event-points">+{{ $activity->pts }} XP</span>@endif</p>
                                <span class="atlas-stamp atlas-stamp--{{ $stamp['tone'] }}">@if ($stampIcon = \App\Support\StampIcon::for($stamp['word']))<x-icon :name="$stampIcon" size="12" />@endif{{ $stamp['word'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            @else
                <div class="atlas-evidence-specimen atlas-evidence-specimen--empty">
                    <div class="atlas-sheet" aria-hidden="true">
                        <span class="atlas-sheet-rule" style="width: 58%"></span>
                        <span class="atlas-sheet-rule" style="width: 82%"></span>
                        <span class="atlas-sheet-rule" style="width: 44%"></span>
                    </div>
                    <div class="atlas-evidence-copy">
                        <p class="atlas-evidence-title">No entries yet</p>
                        <p class="atlas-evidence-meta">Finish a challenge and the first record will be mounted here.</p>
                        <p class="game-copy-line">{{ \App\Support\GameCopy::line('empty.activity') }}</p>
                    </div>
                </div>
            @endif
        </section>

        @if ($focusCard !== null)
            <section class="atlas-dashboard-focus" aria-labelledby="focus-heading">
                <div class="atlas-dashboard-focus-copy">
                    <h2 id="focus-heading">Where to focus</h2>
                    <p class="atlas-dashboard-focus-title">{{ $focusCard['title'] }}</p>
                    <p class="atlas-dashboard-focus-subtitle">{{ $focusCard['subtitle'] }}</p>
                </div>
                @php
                    $focusAccessible = $focusCard['accessible'] ?? true;
                    $focusIsAssessment = str_contains($focusCard['href'], '/assessments/');
                    $focusHref = $focusIsAssessment ? route('recommendations') : $focusCard['href'];
                    $focusCta = $focusIsAssessment ? 'View recommendations' : $focusCard['cta'];
                @endphp
                @if ($focusAccessible)
                    <a href="{{ $focusHref }}" class="btn btn-secondary">{{ $focusCta }}</a>
                @else
                    <p class="atlas-dashboard-focus-locked">{{ $focusCard['locked_reason'] ?? 'Not available yet.' }}</p>
                @endif
            </section>
        @endif

        <footer class="atlas-dashboard-foot" aria-hidden="true">
            <span>End of entries</span>
        </footer>
    </div>
@endsection
