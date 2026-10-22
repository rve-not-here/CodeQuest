@extends('layouts.app', ['role' => $role])

@section('title', 'Student Progress')

@section('content')
    @php
        $courseStates = [
            'COMPLETED' => ['label' => 'Completed', 'stamp' => 'atlas-stamp--grn'],
            'READY' => ['label' => 'Ready', 'stamp' => 'atlas-stamp--vio'],
            'IN PROGRESS' => ['label' => 'In progress', 'stamp' => 'atlas-stamp--vio'],
            'LOCKED' => ['label' => 'Locked', 'stamp' => 'atlas-stamp--ink'],
        ];
        $sectionStates = [
            'DONE' => ['label' => 'Done', 'stamp' => 'atlas-stamp--grn'],
            'IN PROGRESS' => ['label' => 'In progress', 'stamp' => 'atlas-stamp--vio'],
            'NOT STARTED' => ['label' => 'Not started', 'stamp' => 'atlas-stamp--ink'],
            'EMPTY' => ['label' => 'No challenges', 'stamp' => 'atlas-stamp--ink'],
        ];
        $bossStates = ['COMPLETED' => 'Passed', 'READY' => 'Ready'];
        $attemptStates = [
            'passed' => ['label' => 'Passed', 'stamp' => 'atlas-stamp--grn'],
            'failed' => ['label' => 'Failed', 'stamp' => 'atlas-stamp--acc'],
            'submitted' => ['label' => 'Awaiting verdict', 'stamp' => 'atlas-stamp--amb'],
            'evaluated' => ['label' => 'Evaluated', 'stamp' => 'atlas-stamp--vio'],
            'started' => ['label' => 'In progress', 'stamp' => 'atlas-stamp--vio'],
        ];
        $competencyStates = [
            'demonstrated' => ['label' => 'Demonstrated', 'stamp' => 'atlas-stamp--grn'],
            'practicing' => ['label' => 'Practicing', 'stamp' => 'atlas-stamp--amb'],
            'developing' => ['label' => 'Developing', 'stamp' => 'atlas-stamp--vio'],
            'not_started' => ['label' => 'Not started', 'stamp' => 'atlas-stamp--ink'],
        ];
        $skillStates = [
            'proficient' => ['label' => 'On track', 'stamp' => 'atlas-stamp--grn'],
            'weak' => ['label' => 'Needs work', 'stamp' => 'atlas-stamp--amb'],
            'unassessed' => ['label' => 'Not assessed', 'stamp' => 'atlas-stamp--ink'],
        ];
        $completedChallenges = $rows->sum(fn (array $row): int => $row['progress']['completed']);
        $totalChallenges = $rows->sum(fn (array $row): int => $row['progress']['total']);
        $bossPassed = $performance->filter(fn (array $row): bool => $row['passed'])->count();
    @endphp
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · {{ $role === 'teacher' ? 'Classroom scope' : 'Platform scope' }}</span>
            <span class="atlas-records-running-links">
                @if ($role !== 'teacher')
                    <a href="{{ route('students') }}">Students →</a>
                @endif
                @if ($role === 'teacher')
                    <a href="{{ route('reports.teacher-student', $student) }}">Student report →</a>
                @endif
            </span>
        </div>

        <header class="atlas-records-heading">
            <h1 class="atlas-records-title">{{ $student->username }}</h1>
            <p class="atlas-records-lede">{{ $student->name ?: $student->username }} · Recorded learning evidence in the current monitoring scope.</p>
            <dl class="log-facts atlas-records-facts" aria-label="Student monitoring scope">
                <div><dt>Courses</dt><dd class="is-numeric">{{ $rows->count() }}</dd></div>
                <div><dt>Challenges</dt><dd class="is-numeric">{{ $completedChallenges }}/{{ $totalChallenges }}</dd></div>
                <div><dt>Boss Challenges passed</dt><dd class="is-numeric">{{ $bossPassed }}</dd></div>
                <div><dt>Scope</dt><dd>{{ $role === 'teacher' ? 'Classroom' : 'Platform' }}</dd></div>
            </dl>
        </header>

        <section class="atlas-callout" data-state="clear" aria-labelledby="monitoring-position-title">
            <div>
                <h2 id="monitoring-position-title" class="atlas-callout-label">Current position</h2>
                @if ($position === null)
                    <p class="atlas-callout-copy">No current learning position in this monitoring scope.</p>
                @elseif ($position['type'] === 'mission')
                    <p class="atlas-callout-figure">{{ $position['mission']->title }}</p>
                    <p class="atlas-callout-copy">{{ $position['course']->name }}@if ($position['section'] !== null) · Section {{ $position['section']->title }}@endif · Next challenge to complete.</p>
                @else
                    <p class="atlas-callout-figure">Boss Challenge outstanding</p>
                    <p class="atlas-callout-copy">{{ $position['course']->name }} · Every challenge is complete. The Boss Challenge is open.</p>
                @endif
            </div>
        </section>

        <section id="monitoring-challenges" class="atlas-record-group" aria-labelledby="monitoring-challenges-title">
            <div class="atlas-record-group-head">
                <div>
                    <span class="atlas-record-entry-label">Register 01</span>
                    <h2 id="monitoring-challenges-title" class="atlas-record-group-title">Coding Challenge evidence</h2>
                </div>
                <p class="atlas-record-group-figure">{{ $rows->count() }} {{ Str::plural('course', $rows->count()) }}</p>
            </div>
            <p class="atlas-record-group-note">Recorded course and section completion. Boss Challenge state is shown separately from Knowledge Check results.</p>
            @forelse ($rows as $row)
                @php
                    $course = $row['course'];
                    $stateView = $courseStates[$row['state']] ?? $courseStates['LOCKED'];
                    $bossLabel = $bossStates[$row['state']] ?? 'Locked';
                @endphp
                <article class="atlas-record-entry">
                    <div class="atlas-record-entry-head">
                        <span class="atlas-record-entry-label">Course {{ str_pad((string) $course->order_num, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="atlas-record-entry-figures">
                            @if ($row['isPosition'])
                                <span class="atlas-stamp atlas-stamp--vio"><x-icon name="locate" size="12" />Current</span>
                            @endif
                            <span class="atlas-stamp {{ $stateView['stamp'] }}">@if ($stampIcon = \App\Support\StampIcon::for($stateView['label']))<x-icon :name="$stampIcon" size="12" />@endif{{ $stateView['label'] }}</span>
                        </span>
                    </div>
                    <h3 class="atlas-record-entry-title">{{ $course->name }}</h3>
                    @if ($course->description)
                        <p class="atlas-record-entry-copy">{{ $course->description }}</p>
                    @endif
                    <dl class="log-facts atlas-record-entry-facts">
                        <div><dt>Challenges</dt><dd class="is-numeric">{{ $row['progress']['completed'] }}/{{ $row['progress']['total'] }}</dd></div>
                        <div><dt>Complete</dt><dd class="is-numeric">{{ $row['progress']['percent'] }}%</dd></div>
                        <div><dt>Boss Challenge</dt><dd>{{ $bossLabel }}</dd></div>
                    </dl>
                    @if ($row['progress']['total'] > 0)
                        <div class="progress atlas-record-entry-bar" role="progressbar" aria-label="{{ $course->name }} challenge progress" aria-valuemin="0" aria-valuemax="{{ $row['progress']['total'] }}" aria-valuenow="{{ $row['progress']['completed'] }}" aria-valuetext="{{ $row['progress']['completed'] }} of {{ $row['progress']['total'] }} challenges complete">
                            <span style="width: {{ $row['progress']['percent'] }}%"></span>
                        </div>
                    @endif
                    @if ($row['sections']->isNotEmpty())
                        <div class="atlas-register-table-wrap">
                            <table class="table-stack atlas-register-table" role="table">
                                <caption>Challenge completion by section</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Section</th>
                                        <th scope="col" class="md:text-right">Completed</th>
                                        <th scope="col" class="md:text-right">Section progress</th>
                                        <th scope="col">State</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($row['sections'] as $sectionRow)
                                        @php
                                            $sectionView = $sectionStates[$sectionRow['state']] ?? $sectionStates['EMPTY'];
                                        @endphp
                                        <tr>
                                            <th scope="row" data-label="Section">{{ $sectionRow['section']?->title ?? 'Additional challenges' }}</th>
                                            <td data-label="Completed" class="md:text-right tabular-nums"><span class="atlas-register-figure">{{ $sectionRow['progress']['completed'] }}/{{ $sectionRow['progress']['total'] }}</span></td>
                                            <td data-label="Section progress" class="md:text-right tabular-nums"><span class="atlas-register-figure">{{ $sectionRow['progress']['percent'] }}%</span></td>
                                            <td data-label="State"><span class="atlas-stamp {{ $sectionView['stamp'] }}">@if ($stampIcon = \App\Support\StampIcon::for($sectionView['label']))<x-icon :name="$stampIcon" size="12" />@endif{{ $sectionView['label'] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </article>
            @empty
                <p class="atlas-register-empty">No courses are available in this monitoring scope.</p>
            @endforelse
        </section>

        <section id="monitoring-checks" class="atlas-record-group" aria-labelledby="monitoring-checks-title">
            <div class="atlas-record-group-head">
                <div>
                    <span class="atlas-record-entry-label">Register 02</span>
                    <h2 id="monitoring-checks-title" class="atlas-record-group-title">Knowledge Check evidence</h2>
                </div>
                <p class="atlas-record-group-figure">{{ $knowledgeCheckAttempts->total() }} {{ Str::plural('attempt', $knowledgeCheckAttempts->total()) }}</p>
            </div>
            <p class="atlas-record-group-note">Submitted attempts in the current monitoring scope. Scores are formative and do not award XP. Question feedback uses the recorded submission snapshots.</p>

            <h3 class="atlas-records-subheading">Questions needing review</h3>
            <p class="atlas-record-group-note">Up to 20 questions with incorrect recorded responses across submitted attempts in this scope. Counts describe answers, not competency or mastery.</p>
            @if ($knowledgeCheckMisconceptions->isEmpty())
                <p class="atlas-register-empty">No incorrect submitted responses in the current scope.</p>
            @else
                <ol class="atlas-records-list">
                    @foreach ($knowledgeCheckMisconceptions as $misconception)
                        <li class="atlas-record-entry atlas-record-entry--compact">
                            <div class="atlas-record-entry-row">
                                <p class="atlas-record-entry-line">{{ $misconception['prompt'] }}</p>
                                <p class="atlas-record-entry-figure">{{ $misconception['incorrect'] }} of {{ $misconception['responses'] }} incorrect</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif

            <h3 class="atlas-records-subheading">Submitted attempts</h3>
            @if ($knowledgeCheckAttempts->isEmpty())
                <p class="atlas-register-empty">No submitted Knowledge Checks in the current scope.</p>
            @else
                <ul class="atlas-attempts">
                    @foreach ($knowledgeCheckAttempts as $checkAttempt)
                        <li>
                            <details class="atlas-attempt">
                                <summary>
                                    <span class="atlas-attempt-title">{{ $checkAttempt->knowledgeCheck->title }}</span>
                                    <span class="atlas-attempt-meta">{{ $checkAttempt->knowledgeCheck->mission->course->name }} · Attempt {{ $checkAttempt->attempt_number }} · {{ $checkAttempt->submitted_at?->format('M d, Y · H:i') }}</span>
                                    <span class="atlas-attempt-figure">{{ $checkAttempt->score }}/{{ $checkAttempt->total_questions }}</span>
                                </summary>
                                <ol class="atlas-attempt-responses">
                                    @foreach ($checkAttempt->responses as $checkResponse)
                                        <li class="atlas-attempt-response" data-result="{{ $checkResponse->is_correct ? 'correct' : 'review' }}">
                                            <p class="atlas-attempt-response-question">{{ $checkResponse->prompt_snapshot }}</p>
                                            <p class="atlas-attempt-response-verdict">{{ $checkResponse->is_correct ? 'Correct' : 'Needs review' }}</p>
                                            <p class="atlas-attempt-response-detail">Selected: {{ $checkResponse->selected_option_snapshot ?? 'No answer' }}</p>
                                            <p class="atlas-attempt-response-detail">Recorded answer: {{ $checkResponse->correct_option_snapshot }}</p>
                                            @if ($checkResponse->explanation_snapshot)
                                                <p class="atlas-attempt-response-detail">{{ $checkResponse->explanation_snapshot }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </details>
                        </li>
                    @endforeach
                </ul>
                @if ($knowledgeCheckAttempts->hasPages())
                    <nav class="atlas-records-pager" aria-label="Knowledge Check attempt pages">
                        <p class="atlas-records-pager-figure">Showing {{ $knowledgeCheckAttempts->firstItem() }}–{{ $knowledgeCheckAttempts->lastItem() }} of {{ $knowledgeCheckAttempts->total() }} · Page {{ $knowledgeCheckAttempts->currentPage() }} of {{ $knowledgeCheckAttempts->lastPage() }}</p>
                        <div class="atlas-records-pager-actions">
                            @if ($knowledgeCheckAttempts->onFirstPage())
                                <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">← Previous</span>
                            @else
                                <a href="{{ $knowledgeCheckAttempts->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Previous</a>
                            @endif
                            @if ($knowledgeCheckAttempts->hasMorePages())
                                <a href="{{ $knowledgeCheckAttempts->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                            @else
                                <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">Next →</span>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </section>

        @if ($performance->isNotEmpty())
            <section id="monitoring-assessment" class="atlas-record-group" aria-labelledby="monitoring-assessment-title">
                <div class="atlas-record-group-head">
                    <div>
                        <span class="atlas-record-entry-label">Register 03</span>
                        <h2 id="monitoring-assessment-title" class="atlas-record-group-title">Assessment performance</h2>
                    </div>
                    <p class="atlas-record-group-figure">{{ $performance->count() }} {{ Str::plural('course', $performance->count()) }}</p>
                </div>
                <p class="atlas-record-group-note">Course-level Boss Challenge results and recorded attempt history.</p>
                @foreach ($performance as $row)
                    @php
                        $rowCourse = $row['course'];
                        $stateView = $courseStates[$row['state']] ?? $courseStates['LOCKED'];
                        $latestVerdict = $row['latest'] === null ? '—' : ($attemptStates[$row['latest']['status']]['label'] ?? 'In progress');
                    @endphp
                    <article class="atlas-record-entry">
                        <div class="atlas-record-entry-head">
                            <span class="atlas-record-entry-label">Boss Challenge record</span>
                            <span class="atlas-stamp {{ $stateView['stamp'] }}">@if ($stampIcon = \App\Support\StampIcon::for($stateView['label']))<x-icon :name="$stampIcon" size="12" />@endif{{ $stateView['label'] }}</span>
                        </div>
                        <h3 class="atlas-record-entry-title">{{ $rowCourse->name }}</h3>
                        <dl class="log-facts atlas-record-entry-facts atlas-record-entry-facts--wide">
                            <div><dt>Attempts</dt><dd class="is-numeric">{{ $row['attemptCount'] }}</dd></div>
                            <div><dt>Last score</dt><dd class="is-numeric">{{ $row['latest'] !== null && $row['latest']['score'] !== null ? $row['latest']['score'].'%' : '—' }}</dd></div>
                            <div><dt>Verdict</dt><dd>{{ $latestVerdict }}</dd></div>
                            <div><dt>Completed</dt><dd class="is-numeric">{{ $row['completedAt']?->format('M d, Y') ?? '—' }}</dd></div>
                        </dl>
                    </article>
                @endforeach

                <h3 class="atlas-records-subheading">Attempt log</h3>
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Boss Challenge attempts grouped by course, newest first within each course</caption>
                        <thead>
                            <tr>
                                <th scope="col">Course</th>
                                <th scope="col">Verdict</th>
                                <th scope="col">Score</th>
                                <th scope="col">Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($attemptLog as $attempt)
                                @php
                                    $attemptView = $attemptStates[$attempt['status']] ?? ['label' => 'In progress', 'stamp' => 'atlas-stamp--vio'];
                                @endphp
                                <tr>
                                    <th scope="row" data-label="Course">{{ $attempt['courseName'] }}</th>
                                    <td data-label="Verdict"><span class="atlas-stamp {{ $attemptView['stamp'] }}">@if ($stampIcon = \App\Support\StampIcon::for($attemptView['label']))<x-icon :name="$stampIcon" size="12" />@endif{{ $attemptView['label'] }}</span></td>
                                    <td data-label="Score" class="md:text-right tabular-nums"><span class="atlas-register-figure">{{ $attempt['score'] !== null ? $attempt['score'].'%' : '—' }}</span></td>
                                    <td data-label="Submitted"><span class="atlas-register-figure">{{ $attempt['submitted_at']?->format('M d, Y · H:i') ?? '—' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($competency->isNotEmpty())
            <section id="monitoring-competency" class="atlas-record-group" aria-labelledby="monitoring-competency-title">
                <div class="atlas-record-group-head">
                    <div>
                        <span class="atlas-record-entry-label">Register 04</span>
                        <h2 id="monitoring-competency-title" class="atlas-record-group-title">Competency</h2>
                    </div>
                    <p class="atlas-record-group-figure">{{ $competency->count() }} {{ Str::plural('course', $competency->count()) }}</p>
                </div>
                <p class="atlas-record-group-note">Course state derived from challenge completion and Boss Challenge results.</p>
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Course competency evidence</caption>
                        <thead>
                            <tr>
                                <th scope="col">Course</th>
                                <th scope="col">State</th>
                                <th scope="col">Challenges</th>
                                <th scope="col">Progress</th>
                                <th scope="col">Wrong submissions</th>
                                <th scope="col">Boss attempts</th>
                                <th scope="col">Boss Challenge</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($competency as $row)
                                @php
                                    $stateView = $competencyStates[$row['state']] ?? $competencyStates['not_started'];
                                    $bossLabel = $row['challengePassed'] ? 'Passed' : ($row['attempts'] > 0 ? 'Attempted, not passed' : 'Not attempted');
                                @endphp
                                <tr>
                                    <th scope="row" data-label="Course">{{ $row['name'] }}</th>
                                    <td data-label="State"><span class="atlas-stamp {{ $stateView['stamp'] }}">@if ($stampIcon = \App\Support\StampIcon::for($stateView['label']))<x-icon :name="$stampIcon" size="12" />@endif{{ $stateView['label'] }}</span></td>
                                    <td data-label="Challenges"><span class="atlas-register-figure">{{ $row['completedMissions'] }}/{{ $row['totalMissions'] }}</span></td>
                                    <td data-label="Progress"><span class="atlas-register-figure">{{ $row['percent'] }}%</span></td>
                                    <td data-label="Wrong submissions"><span class="atlas-register-figure">{{ $row['wrongSubmissions'] }}</span></td>
                                    <td data-label="Boss attempts"><span class="atlas-register-figure">{{ $row['attempts'] }}</span></td>
                                    <td data-label="Boss Challenge">{{ $bossLabel }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($skills->isNotEmpty())
            <section id="monitoring-skills" class="atlas-record-group" aria-labelledby="monitoring-skills-title">
                <div class="atlas-record-group-head">
                    <div>
                        <span class="atlas-record-entry-label">Register 05</span>
                        <h2 id="monitoring-skills-title" class="atlas-record-group-title">Skill competency</h2>
                    </div>
                    <p class="atlas-record-group-figure">{{ $skills->count() }} {{ Str::plural('skill', $skills->count()) }}</p>
                </div>
                <p class="atlas-record-group-note">Knowledge Check and Coding Challenge evidence.</p>
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Skill assessment evidence</caption>
                        <thead>
                            <tr>
                                <th scope="col">Skill</th>
                                <th scope="col">State</th>
                                <th scope="col">Evidence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($skills as $skill)
                                @php
                                    $skillView = $skillStates[$skill['state']] ?? $skillStates['unassessed'];
                                @endphp
                                <tr>
                                    <th scope="row" data-label="Skill">{{ $skill['label'] }}</th>
                                    <td data-label="State"><span class="atlas-stamp {{ $skillView['stamp'] }}">@if ($stampIcon = \App\Support\StampIcon::for($skillView['label']))<x-icon :name="$stampIcon" size="12" />@endif{{ $skillView['label'] }}</span></td>
                                    <td data-label="Evidence">{{ $skill['percentage'] === null ? 'No evidence recorded yet.' : $skill['percentage'].'%' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($recommendations->isNotEmpty())
            <section id="monitoring-recommendations" class="atlas-record-group" aria-labelledby="monitoring-recommendations-title">
                <div class="atlas-record-group-head">
                    <div>
                        <span class="atlas-record-entry-label">Register 06</span>
                        <h2 id="monitoring-recommendations-title" class="atlas-record-group-title">Recommendations</h2>
                    </div>
                    <p class="atlas-record-group-figure">{{ $recommendations->count() }} {{ Str::plural('action', $recommendations->count()) }}</p>
                </div>
                <p class="atlas-record-group-note">Recorded actions only. This view is read-only.</p>
                <x-recommendation-cards :recommendations="$recommendations" :actionable="false" />
            </section>
        @endif
    </div>
@endsection
