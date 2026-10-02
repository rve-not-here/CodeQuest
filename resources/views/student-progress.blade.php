@extends('layouts.app', ['role' => $role])

@section('title', 'Student Progress')

@section('content')
    @if (auth()->user()->role === 'teacher')
        <a href="{{ route('reports.teacher-student', $student) }}" class="btn btn-secondary mb-4">VIEW STUDENT REPORT →</a>
    @endif
    <section class="panel mb-5 p-5" aria-labelledby="monitoring-checks-title">
        <h2 id="monitoring-checks-title" class="text-lg font-semibold">Knowledge Check evidence</h2>
        <p class="mt-2 text-sm text-fg-muted">Submitted attempt history in your current classroom scope. Scores are formative and do not award XP. Question feedback uses the recorded submission snapshots.</p>
        <h3 class="mt-4 text-sm font-semibold">Questions needing review</h3>
        <p class="mt-1 text-xs text-fg-subtle">Up to 20 questions with incorrect recorded responses across submitted attempts in this scope. Counts describe answers, not competency or mastery.</p>
        <ul class="mt-3 space-y-2 text-sm">
            @forelse ($knowledgeCheckMisconceptions as $misconception)
                <li>{{ $misconception['prompt'] }} · {{ $misconception['incorrect'] }} incorrect / {{ $misconception['responses'] }} recorded responses</li>
            @empty
                <li class="text-fg-subtle">No incorrect submitted responses in your current scope.</li>
            @endforelse
        </ul>
        @forelse ($knowledgeCheckAttempts as $checkAttempt)
            <details class="mt-4 border-t border-line pt-3">
                <summary class="cursor-pointer text-sm">{{ $checkAttempt->knowledgeCheck->title }} · {{ $checkAttempt->knowledgeCheck->mission->course->name }} · Attempt {{ $checkAttempt->attempt_number }} · {{ $checkAttempt->score }}/{{ $checkAttempt->total_questions }} · {{ $checkAttempt->submitted_at?->format('Y-m-d H:i') }}</summary>
                <ol class="mt-3 space-y-3">
                    @foreach ($checkAttempt->responses as $checkResponse)
                        <li class="text-sm">
                            <p>{{ $checkResponse->prompt_snapshot }}</p>
                            <p class="text-fg-muted">Selected: {{ $checkResponse->selected_option_snapshot ?? 'No answer' }}</p>
                            <p class="{{ $checkResponse->is_correct ? 'text-accent' : 'text-warning' }}">{{ $checkResponse->is_correct ? 'Correct' : 'Needs review' }} · Recorded answer: {{ $checkResponse->correct_option_snapshot }}</p>
                            <p class="text-fg-subtle">{{ $checkResponse->explanation_snapshot }}</p>
                        </li>
                    @endforeach
                </ol>
            </details>
        @empty
            <p class="mt-4 text-sm text-fg-subtle">No submitted Knowledge Checks in your current scope.</p>
        @endforelse
        <div class="mt-4">{{ $knowledgeCheckAttempts->links() }}</div>
    </section>
    <x-page-header
        title="Student Progress"
        subtitle="{{ $student->username }} · {{ $student->name }}"
        icon="◷"
    >
        <x-slot:actions>
            <a href="{{ route('students') }}" class="btn-ghost">◀ ALL STUDENTS</a>
        </x-slot:actions>
    </x-page-header>

    <x-panel title="CURRENT POSITION" class="mb-6">
        @if ($position === null)
            <p class="font-body text-lg text-ink">
                ALL COURSES CLEARED — every active directive's Boss Challenge has been passed.
            </p>
        @elseif ($position['type'] === 'mission')
            <p class="font-body text-lg text-ink">
                Continue at <span class="text-phosphor">{{ $position['course']->name }}</span>
                @if ($position['section'] !== null)
                    · Section <span class="text-amber">{{ $position['section']->title }}</span>
                @endif
                · Mission <span class="text-amber">{{ $position['mission']->title }}</span>
            </p>
        @else
            <p class="font-body text-lg text-ink">
                Course work complete at <span class="text-phosphor">{{ $position['course']->name }}</span>
                — Boss Challenge outstanding.
            </p>
        @endif
    </x-panel>

    @if ($recommendations->isNotEmpty())
        <x-panel title="RECOMMENDATIONS" class="mb-6">
            <x-recommendation-cards :recommendations="$recommendations" :actionable="false" />
        </x-panel>
    @endif

    @if ($performance->isNotEmpty())
        <x-panel title="Assessment Performance" class="mb-6">
            <div class="overflow-x-auto">
                <table class="table-stack w-full text-left">
                    <thead>
                        <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                            <th class="px-4 py-2">Course</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Attempts</th>
                            <th class="px-4 py-2">Last score</th>
                            <th class="px-4 py-2">Verdict</th>
                            <th class="px-4 py-2">Completed</th>
                        </tr>
                    </thead>
                    <tbody class="font-body text-lg">
                        @foreach ($performance as $row)
                            @php
                                $stateTone = match ($row['state']) {
                                    'COMPLETED' => 'phosphor',
                                    'READY' => 'amber',
                                    'IN PROGRESS' => 'cyan',
                                    default => 'dim',
                                };
                                $verdictLabel = '—';
                                $verdictTone = 'dim';
                                if ($row['latest'] !== null) {
                                    $verdictLabel = match ($row['latest']['status']) {
                                        'passed' => 'PASSED',
                                        'failed' => 'FAILED',
                                        'submitted' => 'AWAITING VERDICT',
                                        default => 'IN PROGRESS',
                                    };
                                    $verdictTone = match ($row['latest']['status']) {
                                        'passed' => 'phosphor',
                                        'failed' => 'alert',
                                        'submitted' => 'amber',
                                        default => 'cyan',
                                    };
                                }
                            @endphp

                            <tr class="border-b border-phosphor-dim/40 align-top">
<td class="px-4 py-3 text-ink" data-label="Course">{{ $row['course']->name }}</td>
                                 <td class="px-4 py-3" data-label="Status">
                                     <x-badge tone="{{ $stateTone }}">{{ $row['state'] }}</x-badge>
                                 </td>
                                 <td class="px-4 py-3 text-phosphor" data-label="Attempts">{{ $row['attemptCount'] }}</td>
                                 <td class="px-4 py-3 text-ink" data-label="Last score">
                                     @if ($row['latest'] !== null && $row['latest']['score'] !== null)
                                         {{ $row['latest']['score'] }}%
                                     @else
                                         <span class="text-phosphor-dim">—</span>
                                     @endif
                                 </td>
                                 <td class="px-4 py-3" data-label="Verdict">
                                     <x-badge tone="{{ $verdictTone }}">{{ $verdictLabel }}</x-badge>
                                 </td>
                                 <td class="px-4 py-3 text-ink" data-label="Completed">
                                     @if ($row['completedAt'] !== null)
                                         {{ $row['completedAt']->format('Y-m-d H:i') }}
                                     @else
                                         <span class="text-phosphor-dim">—</span>
                                     @endif
                                 </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($attemptLog->isNotEmpty())
                <div class="mt-4 border-t border-phosphor-dim/40 pt-4">
                    <p class="text-sm font-bold text-phosphor-dim mb-2">
                        Attempt log
                    </p>
                    <div class="overflow-x-auto">
                        <table class="table-stack w-full text-left">
                            <thead>
                                <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                                    <th class="px-4 py-2">Course</th>
                                    <th class="px-4 py-2">Verdict</th>
                                    <th class="px-4 py-2">Score</th>
                                    <th class="px-4 py-2">Submitted</th>
                                </tr>
                            </thead>
                            <tbody class="font-body text-lg">
                                @foreach ($attemptLog as $attempt)
                                    @php
                                        $logTone = match ($attempt['status']) {
                                            'passed' => 'phosphor',
                                            'failed' => 'alert',
                                            'submitted' => 'amber',
                                            default => 'cyan',
                                        };
                                        $logLabel = match ($attempt['status']) {
                                            'passed' => 'PASSED',
                                            'failed' => 'FAILED',
                                            'submitted' => 'AWAITING VERDICT',
                                            default => 'IN PROGRESS',
                                        };
                                    @endphp

                                    <tr class="border-b border-phosphor-dim/40 align-top">
<td class="px-4 py-2 text-ink" data-label="Course">{{ $attempt['courseName'] }}</td>
                                         <td class="px-4 py-2" data-label="Verdict">
                                             <x-badge tone="{{ $logTone }}">{{ $logLabel }}</x-badge>
                                         </td>
                                         <td class="px-4 py-2 text-ink" data-label="Score">
                                             @if ($attempt['score'] !== null)
                                                 {{ $attempt['score'] }}%
                                             @else
                                                 <span class="text-phosphor-dim">—</span>
                                             @endif
                                         </td>
                                         <td class="px-4 py-2 text-[15px] text-ink" data-label="Submitted">
                                            @if ($attempt['submitted_at'] !== null)
                                                {{ $attempt['submitted_at']->format('Y-m-d H:i') }}
                                            @else
                                                <span class="text-phosphor-dim">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </x-panel>
    @endif

    @if ($competency->isNotEmpty())
        <x-panel title="Competency" class="mb-6">
            <div class="overflow-x-auto">
                <table class="table-stack w-full text-left">
                    <thead>
                        <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                            <th class="px-4 py-2">Skill</th>
                            <th class="px-4 py-2">State</th>
                            <th class="px-4 py-2">Missions</th>
                            <th class="px-4 py-2">Progress</th>
                            <th class="px-4 py-2">Wrong subs</th>
                            <th class="px-4 py-2">Attempts</th>
                            <th class="px-4 py-2">Boss Challenge</th>
                        </tr>
                    </thead>
                    <tbody class="font-body text-lg">
                        @foreach ($competency as $row)
                            @php
                                $stateLabel = strtoupper(str_replace('_', ' ', $row['state']));
                                $stateTone = match ($row['state']) {
                                    'demonstrated' => 'phosphor',
                                    'practicing' => 'amber',
                                    'developing' => 'cyan',
                                    default => 'dim',
                                };
                                $challenge = $row['challengePassed']
                                    ? ['label' => 'PASSED', 'tone' => 'phosphor']
                                    : ($row['attempts'] > 0
                                        ? ['label' => 'ATTEMPTED, NOT PASSED', 'tone' => 'amber']
                                        : ['label' => 'NOT ATTEMPTED', 'tone' => 'dim']);
                            @endphp

                            <tr class="border-b border-phosphor-dim/40 align-top">
<td class="px-4 py-3 text-ink" data-label="Skill">{{ $row['name'] }}</td>
                                 <td class="px-4 py-3" data-label="State">
                                     <x-badge tone="{{ $stateTone }}">{{ $stateLabel }}</x-badge>
                                 </td>
                                 <td class="px-4 py-3 text-phosphor" data-label="Missions">
                                     {{ $row['completedMissions'] }}/{{ $row['totalMissions'] }}
                                 </td>
                                 <td class="px-4 py-3 text-ink" data-label="Progress">
                                     {{ $row['percent'] }}%
                                 </td>
                                 <td class="px-4 py-3 text-ink" data-label="Wrong subs">{{ $row['wrongSubmissions'] }}</td>
                                 <td class="px-4 py-3 text-ink" data-label="Attempts">{{ $row['attempts'] }}</td>
                                 <td class="px-4 py-3" data-label="Boss Challenge">
                                     <x-badge tone="{{ $challenge['tone'] }}">{{ $challenge['label'] }}</x-badge>
                                 </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>
    @endif

    @if ($skills->isNotEmpty())
        <x-panel title="Skill Competency" class="mb-6">
            <div class="overflow-x-auto">
                <table class="table-stack w-full text-left">
                    <thead>
                        <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                            <th class="px-4 py-2">Skill</th>
                            <th class="px-4 py-2">State</th>
                            <th class="px-4 py-2">Evidence</th>
                        </tr>
                    </thead>
                    <tbody class="font-body text-lg">
                        @foreach ($skills as $skill)
                            @php
                                $skillTone = match ($skill['state']) {
                                    'proficient' => 'phosphor',
                                    'weak' => 'amber',
                                    default => 'dim',
                                };
                                $skillLabel = match ($skill['state']) {
                                    'proficient' => 'ON TRACK',
                                    'weak' => 'NEEDS WORK',
                                    default => 'NOT ASSESSED',
                                };
                            @endphp
                            <tr class="border-b border-phosphor-dim/40 align-top">
                                <td class="px-4 py-3 text-ink" data-label="Skill">{{ $skill['label'] }}</td>
                                <td class="px-4 py-3" data-label="State">
                                    <x-badge tone="{{ $skillTone }}">{{ $skillLabel }}</x-badge>
                                </td>
                                <td class="px-4 py-3 text-ink" data-label="Evidence">
                                    @if ($skill['percentage'] === null)
                                        No evidence recorded yet.
                                    @else
                                        {{ $skill['percentage'] }}%
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>
    @endif

    @if ($rows->isEmpty())
        <x-status-message type="info" title="NO COURSES">
            No course directives are loaded for this operative. Awaiting new
            directives from Command.
        </x-status-message>
    @endif

    <div class="space-y-6">
        @foreach ($rows as $row)
            @php
                $course = $row['course'];
                $state = $row['state'];
                $stateTone = match ($state) {
                    'COMPLETED' => 'phosphor',
                    'READY' => 'amber',
                    'IN PROGRESS' => 'cyan',
                    default => 'dim',
                };
                $challengeTone = $state === 'COMPLETED' ? 'phosphor' : ($state === 'READY' ? 'amber' : 'dim');
                $challengeLabel = $state === 'COMPLETED'
                    ? 'PASSED'
                    : ($state === 'READY' ? 'READY' : 'LOCKED');
            @endphp

            <x-panel title="{{ $course->name }}">
                <x-slot:actions>
                    @if ($row['isPosition'])
                        <x-badge tone="cyan">CURRENT</x-badge>
                    @endif
                    <x-badge tone="{{ $stateTone }}">
                        {{ $row['progress']['completed'] }}/{{ $row['progress']['total'] }}
                    </x-badge>
                    <x-badge tone="{{ $stateTone }}">{{ $state }}</x-badge>
                    <x-badge tone="{{ $challengeTone }}">BOSS CHALLENGE: {{ $challengeLabel }}</x-badge>
                </x-slot:actions>

                @if ($course->description)
                    <p class="font-body text-[15px] text-ink mb-4">{{ $course->description }}</p>
                @endif

                <x-progress-bar
                    label="Course Progress"
                    :total="$row['progress']['total']"
                    :current="$row['progress']['completed']"
                    tone="{{ $state === 'COMPLETED' ? 'phosphor' : 'amber' }}"
                />

                <div class="mt-5 space-y-5">
                    @foreach ($row['sections'] as $sectionRow)
                        @php
                            $section = $sectionRow['section'];
                            $sectionState = $sectionRow['state'];
                            $sectionTone = match ($sectionState) {
                                'DONE' => 'phosphor',
                                'IN PROGRESS' => 'amber',
                                default => 'dim',
                            };
                            $xYTone = $sectionRow['progress']['percent'] === 100 && $sectionRow['progress']['total'] > 0
                                ? 'phosphor'
                                : ($sectionRow['progress']['total'] === 0 ? 'dim' : 'cyan');
                        @endphp

                        <div>
                            <div class="flex items-baseline justify-between gap-3 mb-2">
                                <h3 class="text-sm font-bold text-amber">
                                    {{ $section?->title ?? 'Additional missions' }}
                                </h3>
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-badge tone="{{ $xYTone }}">
                                        {{ $sectionRow['progress']['completed'] }}/{{ $sectionRow['progress']['total'] }}
                                    </x-badge>
                                    <x-badge tone="{{ $sectionTone }}">{{ $sectionState }}</x-badge>
                                </div>
                            </div>

                            <x-progress-bar
                                label="Section Progress"
                                :total="$sectionRow['progress']['total']"
                                :current="$sectionRow['progress']['completed']"
                                tone="{{ $sectionState === 'DONE' ? 'phosphor' : 'amber' }}"
                            />
                        </div>
                    @endforeach
                </div>
            </x-panel>
        @endforeach
    </div>
@endsection
