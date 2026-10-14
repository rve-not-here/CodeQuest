@extends('layouts.app', ['role' => $role])

@section('title', 'Student Progress')

@section('content')
    <div class="teacher-operations">
        <header class="teacher-context">
            <div><h1>Student Progress</h1><p>{{ $student->username }} · {{ $student->name }}</p><p class="teacher-state">{{ $role === 'teacher' ? 'Classroom scope' : 'SYSTEM-WIDE' }}</p></div>
            <div class="teacher-context-actions">
                <a href="{{ route('students') }}" class="btn-ghost">◀ ALL STUDENTS</a>
                @if ($role === 'teacher')<a href="{{ route('reports.teacher-student', $student) }}" class="btn-primary">VIEW STUDENT REPORT →</a>@endif
            </div>
        </header>
        <section class="teacher-current-position" aria-labelledby="monitoring-position">
            <h2 id="monitoring-position">CURRENT POSITION</h2>
            @if ($position === null)
                <p>No current learning position in this monitoring scope.</p>
            @elseif ($position['type'] === 'mission')
                <p>Continue at <strong>{{ $position['course']->name }}</strong>@if ($position['section'] !== null) · Section <strong>{{ $position['section']->title }}</strong>@endif · Mission <strong>{{ $position['mission']->title }}</strong></p>
            @else
                <p>Course work complete at <strong>{{ $position['course']->name }}</strong> · Boss Challenge outstanding.</p>
            @endif
        </section>
        <nav class="teacher-evidence-navigation" aria-label="Student evidence">
            <a href="#monitoring-challenges">Coding Challenges</a>
            <a href="#monitoring-checks-title">Knowledge Checks</a>
            @if ($performance->isNotEmpty())<a href="#monitoring-assessment">Boss Challenge history</a>@endif
            @if ($competency->isNotEmpty())<a href="#monitoring-competency">Competency</a>@endif
            @if ($skills->isNotEmpty())<a href="#monitoring-skills">Skill evidence</a>@endif
        </nav>
        <section class="teacher-record-section" aria-labelledby="monitoring-challenges">
            <h2 id="monitoring-challenges">Coding Challenge evidence</h2>
            <p class="teacher-note">Recorded course and section completion. Boss Challenge state is shown separately from Knowledge Check results.</p>
            @forelse ($rows as $row)
                @php
                    $course = $row['course'];
                    $state = $row['state'];
                    $challengeLabel = $state === 'COMPLETED' ? 'PASSED' : ($state === 'READY' ? 'READY' : 'LOCKED');
                @endphp
                <article class="teacher-course-record">
                    <header class="teacher-section-heading">
                        <h3>{{ $course->name }}</h3>
                        <p class="teacher-state">@if ($row['isPosition']) CURRENT · @endif{{ $state }} · {{ $row['progress']['completed'] }}/{{ $row['progress']['total'] }} · BOSS CHALLENGE: {{ $challengeLabel }}</p>
                    </header>
                    @if ($course->description)<p class="teacher-note">{{ $course->description }}</p>@endif
                    <p class="teacher-state">Course Progress {{ $row['progress']['percent'] }}%</p>
                    @if ($row['sections']->isNotEmpty())
                        <table class="table-stack teacher-evidence-table" role="table">
                            <caption>Challenge completion by section</caption>
                            <thead><tr><th scope="col">Section</th><th scope="col">Completed</th><th scope="col">Section Progress</th><th scope="col">State</th></tr></thead>
                            <tbody>
                                @foreach ($row['sections'] as $sectionRow)
                                    <tr>
                                        <th scope="row" data-label="Section">{{ $sectionRow['section']?->title ?? 'Additional missions' }}</th>
                                        <td data-label="Completed">{{ $sectionRow['progress']['completed'] }}/{{ $sectionRow['progress']['total'] }}</td>
                                        <td data-label="Section Progress">{{ $sectionRow['progress']['percent'] }}%</td>
                                        <td data-label="State"><span class="teacher-state">{{ $sectionRow['state'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </article>
            @empty
                <div class="teacher-empty"><h3>NO COURSES</h3><p>No courses are available in this monitoring scope.</p></div>
            @endforelse
        </section>
        <section class="teacher-record-section" aria-labelledby="monitoring-checks-title">
            <h2 id="monitoring-checks-title">Knowledge Check evidence</h2>
            <p class="teacher-note">Submitted attempt history in your current classroom scope. Scores are formative and do not award XP. Question feedback uses the recorded submission snapshots.</p>
            <h3>Questions needing review</h3>
            <p class="teacher-note">Up to 20 questions with incorrect recorded responses across submitted attempts in this scope. Counts describe answers, not competency or mastery.</p>
            <ul class="teacher-record-list">
                @forelse ($knowledgeCheckMisconceptions as $misconception)
                    <li><span>{{ $misconception['prompt'] }}</span><span class="teacher-state">{{ $misconception['incorrect'] }} incorrect / {{ $misconception['responses'] }} recorded responses</span></li>
                @empty
                    <li class="teacher-note">No incorrect submitted responses in your current scope.</li>
                @endforelse
            </ul>
            @forelse ($knowledgeCheckAttempts as $checkAttempt)
                <details class="teacher-attempt-record">
                    <summary>{{ $checkAttempt->knowledgeCheck->title }} · {{ $checkAttempt->knowledgeCheck->mission->course->name }} · Attempt {{ $checkAttempt->attempt_number }} · {{ $checkAttempt->score }}/{{ $checkAttempt->total_questions }} · {{ $checkAttempt->submitted_at?->format('Y-m-d H:i') }}</summary>
                    <ol class="teacher-response-list">
                        @foreach ($checkAttempt->responses as $checkResponse)
                            <li>
                                <p>{{ $checkResponse->prompt_snapshot }}</p>
                                <p class="teacher-note">Selected: {{ $checkResponse->selected_option_snapshot ?? 'No answer' }}</p>
                                <p class="{{ $checkResponse->is_correct ? 'text-success' : 'text-warning' }}">{{ $checkResponse->is_correct ? 'Correct' : 'Needs review' }} · Recorded answer: {{ $checkResponse->correct_option_snapshot }}</p>
                                <p class="teacher-note">{{ $checkResponse->explanation_snapshot }}</p>
                            </li>
                        @endforeach
                    </ol>
                </details>
            @empty
                <p class="teacher-note">No submitted Knowledge Checks in your current scope.</p>
            @endforelse
            <div class="teacher-pagination">{{ $knowledgeCheckAttempts->links() }}</div>
        </section>
        @if ($performance->isNotEmpty())
            <section class="teacher-record-section" aria-labelledby="monitoring-assessment">
                <h2 id="monitoring-assessment">Assessment Performance</h2>
                <p class="teacher-note">Course-level Boss Challenge results and recorded attempt history.</p>
                <table class="table-stack teacher-evidence-table" role="table">
                    <caption>Boss Challenge performance by course</caption>
                    <thead><tr><th scope="col">Course</th><th scope="col">Status</th><th scope="col">Attempts</th><th scope="col">Last score</th><th scope="col">Verdict</th><th scope="col">Completed</th></tr></thead>
                    <tbody>
                        @foreach ($performance as $row)
                            @php
                                $verdictLabel = $row['latest'] === null ? '—' : match ($row['latest']['status']) {
                                    'passed' => 'PASSED', 'failed' => 'FAILED', 'submitted' => 'AWAITING VERDICT', default => 'IN PROGRESS',
                                };
                            @endphp
                            <tr>
                                <th scope="row" data-label="Course">{{ $row['course']->name }}</th>
                                <td data-label="Status"><span class="teacher-state">{{ $row['state'] }}</span></td>
                                <td data-label="Attempts">{{ $row['attemptCount'] }}</td>
                                <td data-label="Last score">{{ $row['latest'] !== null && $row['latest']['score'] !== null ? $row['latest']['score'].'%' : '—' }}</td>
                                <td data-label="Verdict"><span class="teacher-state">{{ $verdictLabel }}</span></td>
                                <td data-label="Completed">{{ $row['completedAt']?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($attemptLog->isNotEmpty())
                    <h3>Attempt log</h3>
                    <table class="table-stack teacher-evidence-table" role="table">
                        <caption>Boss Challenge attempts, newest first</caption>
                        <thead><tr><th scope="col">Course</th><th scope="col">Verdict</th><th scope="col">Score</th><th scope="col">Submitted</th></tr></thead>
                        <tbody>
                            @foreach ($attemptLog as $attempt)
                                @php($logLabel = match ($attempt['status']) { 'passed' => 'PASSED', 'failed' => 'FAILED', 'submitted' => 'AWAITING VERDICT', default => 'IN PROGRESS' })
                                <tr><th scope="row" data-label="Course">{{ $attempt['courseName'] }}</th><td data-label="Verdict"><span class="teacher-state">{{ $logLabel }}</span></td><td data-label="Score">{{ $attempt['score'] !== null ? $attempt['score'].'%' : '—' }}</td><td data-label="Submitted">{{ $attempt['submitted_at']?->format('Y-m-d H:i') ?? '—' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        @endif
        @if ($competency->isNotEmpty())
            <section class="teacher-record-section" aria-labelledby="monitoring-competency">
                <h2 id="monitoring-competency">Competency</h2>
                <table class="table-stack teacher-evidence-table" role="table">
                    <caption>Course competency evidence</caption>
                    <thead><tr><th scope="col">Skill</th><th scope="col">State</th><th scope="col">Missions</th><th scope="col">Progress</th><th scope="col">Wrong subs</th><th scope="col">Attempts</th><th scope="col">Boss Challenge</th></tr></thead>
                    <tbody>
                        @foreach ($competency as $row)
                            @php($challengeLabel = $row['challengePassed'] ? 'PASSED' : ($row['attempts'] > 0 ? 'ATTEMPTED, NOT PASSED' : 'NOT ATTEMPTED'))
                            <tr><th scope="row" data-label="Skill">{{ $row['name'] }}</th><td data-label="State"><span class="teacher-state">{{ strtoupper(str_replace('_', ' ', $row['state'])) }}</span></td><td data-label="Missions">{{ $row['completedMissions'] }}/{{ $row['totalMissions'] }}</td><td data-label="Progress">{{ $row['percent'] }}%</td><td data-label="Wrong subs">{{ $row['wrongSubmissions'] }}</td><td data-label="Attempts">{{ $row['attempts'] }}</td><td data-label="Boss Challenge"><span class="teacher-state">{{ $challengeLabel }}</span></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
        @if ($skills->isNotEmpty())
            <section class="teacher-record-section" aria-labelledby="monitoring-skills">
                <h2 id="monitoring-skills">Skill Competency</h2>
                <table class="table-stack teacher-evidence-table" role="table">
                    <caption>Skill assessment evidence</caption>
                    <thead><tr><th scope="col">Skill</th><th scope="col">State</th><th scope="col">Evidence</th></tr></thead>
                    <tbody>
                        @foreach ($skills as $skill)
                            @php($skillLabel = match ($skill['state']) { 'proficient' => 'ON TRACK', 'weak' => 'NEEDS WORK', default => 'NOT ASSESSED' })
                            <tr><th scope="row" data-label="Skill">{{ $skill['label'] }}</th><td data-label="State"><span class="teacher-state">{{ $skillLabel }}</span></td><td data-label="Evidence">{{ $skill['percentage'] === null ? 'No evidence recorded yet.' : $skill['percentage'].'%' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
        @if ($recommendations->isNotEmpty())
            <section class="teacher-record-section" aria-labelledby="monitoring-recommendations"><h2 id="monitoring-recommendations">RECOMMENDATIONS</h2><x-recommendation-cards :recommendations="$recommendations" :actionable="false" /></section>
        @endif
    </div>
@endsection
