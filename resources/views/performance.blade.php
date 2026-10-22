@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Performance Summary')

@section('content')
    @php
        $courseStates = [
            'COMPLETED' => ['label' => 'Completed', 'stamp' => 'atlas-stamp--grn'],
            'READY' => ['label' => 'Ready', 'stamp' => 'atlas-stamp--vio'],
            'IN PROGRESS' => ['label' => 'In progress', 'stamp' => 'atlas-stamp--vio'],
            'LOCKED' => ['label' => 'Locked', 'stamp' => 'atlas-stamp--ink'],
        ];
        $challengesDone = $registers->sum(fn (array $row): int => $row['challenges']['completed']);
        $challengesTotal = $registers->sum(fn (array $row): int => $row['challenges']['total']);
        $checksSubmitted = $registers->sum(fn (array $row): int => $row['knowledge_checks']['submitted']);
        $checksPublished = $registers->sum(fn (array $row): int => $row['knowledge_checks']['published']);
        $questionsCorrect = $registers->sum(fn (array $row): int => $row['knowledge_checks']['correct']);
        $questionsAnswered = $registers->sum(fn (array $row): int => $row['knowledge_checks']['questions']);
        $bossPassed = $registers->filter(fn (array $row): bool => $row['boss']['passed'])->count();
    @endphp
    <div class="atlas-records">
        <header class="atlas-records-heading">
            <h1 class="atlas-records-title">Performance summary</h1>
            <p class="atlas-records-lede">Coding Challenges, Knowledge Checks, and Boss Challenges in one register — each figure recorded by the service that owns it.</p>
            @if ($registers->isNotEmpty())
                <dl class="log-facts atlas-records-facts">
                    <div>
                        <dt>Coding Challenges</dt>
                        <dd class="is-numeric">{{ $challengesDone }}/{{ $challengesTotal }}</dd>
                    </div>
                    <div>
                        <dt>Knowledge Checks</dt>
                        <dd class="is-numeric">{{ $checksSubmitted }}/{{ $checksPublished }} submitted</dd>
                    </div>
                    <div>
                        <dt>Check questions</dt>
                        <dd class="is-numeric">{{ $questionsAnswered > 0 ? $questionsCorrect.'/'.$questionsAnswered.' correct' : 'No evidence' }}</dd>
                    </div>
                    <div>
                        <dt>Boss Challenges passed</dt>
                        <dd class="is-numeric">{{ $bossPassed }}/{{ $registers->count() }}</dd>
                    </div>
                </dl>
            @endif
        </header>

        <details class="atlas-records-guide">
            <summary>How to read this register</summary>
            <ul>
                <li><strong>Coding Challenges</strong> counts completed challenges out of the course total.</li>
                <li><strong>Knowledge Checks</strong> counts submitted checks; questions use the latest submitted attempt per check.</li>
                <li><strong>Boss Challenge</strong> reports the recorded verdict, best score and attempt count. A later failed retry never erases a pass.</li>
                <li>Locked courses still show their recorded evidence; “No evidence” means the source service has no result yet.</li>
            </ul>
        </details>

        @if ($registers->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="no-registers-title">
                <h2 id="no-registers-title" class="atlas-records-empty-title">No results recorded yet</h2>
                <p class="atlas-records-empty-copy">Complete a challenge, submit a Knowledge Check, or attempt a Boss Challenge and its result will appear here.</p>
                <a href="{{ route('learning-path') }}" class="btn btn-secondary">Open learning path <span aria-hidden="true">→</span></a>

            </section>
        @else
            <h2 class="atlas-records-subheading">Results by course</h2>
            <p class="atlas-record-group-note">Knowledge Check questions count the latest submitted attempt per check.</p>
            <div class="atlas-register-table-wrap">
                <table class="table-stack atlas-register-table">
                    <caption>Results by course</caption>
                    <thead>
                        <tr>
                            <th scope="col">Course</th>
                            <th scope="col">State</th>
                            <th scope="col" class="md:text-right">Coding Challenges</th>
                            <th scope="col" class="md:text-right">Knowledge Checks</th>
                            <th scope="col" class="md:text-right">Check questions</th>
                            <th scope="col">Boss Challenge</th>
                            <th scope="col" class="md:text-right">Boss best score</th>
                            <th scope="col" class="md:text-right">Boss attempts</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($registers as $row)
                            @php
                                $stateView = $courseStates[$row['state']] ?? $courseStates['LOCKED'];
                                $checks = $row['knowledge_checks'];
                                $boss = $row['boss'];
                                $bossState = $boss['passed']
                                    ? 'Passed'
                                    : ($boss['attempts'] > 0 ? 'Attempted, not passed' : 'Not attempted');
                            @endphp
                            <tr data-course-state="{{ $row['state'] }}">
                                <th scope="row" data-label="Course">{{ $row['course']->name }}</th>
                                <td data-label="State">
                                    <span class="atlas-stamp {{ $stateView['stamp'] }}">@if ($stampIcon = \App\Support\StampIcon::for($stateView['label']))<x-icon :name="$stampIcon" size="12" />@endif{{ $stateView['label'] }}</span>
                                </td>
                                <td data-label="Coding Challenges" class="md:text-right tabular-nums">{{ $row['challenges']['completed'] }}/{{ $row['challenges']['total'] }} complete</td>
                                <td data-label="Knowledge Checks" class="md:text-right tabular-nums">{{ $checks['submitted'] }}/{{ $checks['published'] }} submitted</td>
                                <td data-label="Check questions" class="md:text-right tabular-nums">{{ $checks['questions'] > 0 ? $checks['correct'].'/'.$checks['questions'].' correct' : 'No evidence' }}</td>
                                <td data-label="Boss Challenge">{{ $bossState }}</td>
                                <td data-label="Boss best score" class="md:text-right">{{ $boss['best'] !== null ? $boss['best'].'%' : '—' }}</td>
                                <td data-label="Boss attempts" class="md:text-right"><span class="atlas-register-figure">{{ $boss['attempts'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($registers->isNotEmpty())
            <div class="atlas-records-toolbar">
                <p class="atlas-records-subheading">Follow the record</p>
                <div class="atlas-record-entry-actions">
                    <a href="{{ route('timeline') }}" class="btn btn-ghost btn-sm">Open learning timeline <span aria-hidden="true">→</span></a>
                    <a href="{{ route('learning-path') }}" class="btn btn-ghost btn-sm">Open learning path <span aria-hidden="true">→</span></a>
                </div>
            </div>
        @endif
    </div>
@endsection
