@extends('layouts.app', ['role' => $role])

@section('title', 'System analytics')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Analytics · Platform scope</span>
            <span class="atlas-stamp atlas-stamp--ink">Read-only</span>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Operations</span>
            <h1 class="atlas-records-title">System analytics</h1>
            <p class="atlas-records-lede">Recorded account, curriculum, learning and XP evidence. Read-only, system-wide.</p>
        </header>

        @php
            $xpFigures = [
                ['label' => 'Awarded', 'value' => $xp['awarded'], 'metric' => 'xp_awarded'],
                ['label' => 'Spent', 'value' => $xp['spent'], 'metric' => 'xp_spent'],
                ['label' => 'Deducted', 'value' => $xp['deducted'], 'metric' => 'xp_deducted'],
                ['label' => 'Outstanding', 'value' => $xp['outstanding'], 'metric' => 'xp_outstanding'],
                ['label' => 'Accounts', 'value' => $xp['accounts'], 'metric' => 'xp_accounts'],
            ];

            $roleFigures = [
                ['label' => 'Students', 'value' => $roles['student'] ?? 0, 'metric' => 'role_student'],
                ['label' => 'Teachers', 'value' => $roles['teacher'] ?? 0, 'metric' => 'role_teacher'],
                ['label' => 'Admins', 'value' => $roles['admin'] ?? 0, 'metric' => 'role_admin'],
                ['label' => 'Operators', 'value' => $roles['operator'] ?? 0, 'metric' => 'role_operator'],
            ];

            $systemFigure = fn (string $key, string $label): array => [
                'label' => $label,
                'value' => $system[$key] !== null ? $system[$key].($key === 'pass_rate' ? '%' : '') : '-',
                'metric' => $key,
            ];

            $userFigures = [
                $systemFigure('users_total', 'Total'),
                $systemFigure('users_active', 'Active'),
                $systemFigure('users_inactive', 'Inactive'),
            ];

            $curriculumFigures = [
                $systemFigure('courses', 'Courses'),
                $systemFigure('sections', 'Sections'),
                $systemFigure('challenges', 'Challenges'),
                $systemFigure('boss_challenges', 'Boss Challenges'),
            ];

            $learningFigures = [
                $systemFigure('active_students', 'Active students (14d)'),
                $systemFigure('completed_challenges', 'Completed challenges'),
                $systemFigure('course_completions', 'Course completions'),
                $systemFigure('attempts', 'Assessment attempts'),
                $systemFigure('passed_attempts', 'Attempts passed'),
                $systemFigure('pass_rate', 'Assessment pass rate'),
            ];
        @endphp

        <section class="atlas-record-group" aria-labelledby="xp-administration-title">
            <div class="atlas-record-group-head">
                <h2 id="xp-administration-title" class="atlas-record-group-title">XP administration</h2>
                <span class="atlas-stamp atlas-stamp--ink">View only</span>
            </div>

            <div class="atlas-record-figures">
                <dl class="log-facts" aria-label="XP totals">
                    @foreach ($xpFigures as $figure)
                        <div>
                            <dt>{{ $figure['label'] }}</dt>
                            <dd class="is-numeric" data-metric="{{ $figure['metric'] }}">{{ $figure['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            @if ($xp['by_type'] === [])
                <section class="atlas-records-empty" aria-labelledby="empty-ledger-title">
                    <h3 id="empty-ledger-title" class="atlas-records-empty-title">Empty ledger</h3>
                    <p class="atlas-records-empty-copy">No XP transactions have been recorded yet.</p>
                </section>
            @else
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>XP ledger sources</caption>
                        <thead>
                            <tr>
                                <th scope="col">Source</th>
                                <th scope="col">Direction</th>
                                <th scope="col">Entries</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($xp['by_type'] as $row)
                                <tr>
                                    <th scope="row" data-label="Source">{{ $row['label'] }}</th>
                                    <td data-label="Direction">{{ ucfirst($row['direction']) }}</td>
                                    <td data-label="Entries" data-metric="xp_entries_{{ $row['type'] }}">{{ $row['entries'] }}</td>
                                    <td data-label="Total" data-metric="xp_total_{{ $row['type'] }}">{{ $row['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="atlas-record-group" aria-labelledby="fleet-title">
            <div class="atlas-record-group-head">
                <h2 id="fleet-title" class="atlas-record-group-title">Fleet by role</h2>
            </div>

            <div class="atlas-record-figures">
                <dl class="log-facts" aria-label="Accounts by role">
                    @foreach ($roleFigures as $figure)
                        <div>
                            <dt>{{ $figure['label'] }}</dt>
                            <dd class="is-numeric" data-metric="{{ $figure['metric'] }}">{{ $figure['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        <section class="atlas-record-group" aria-labelledby="users-title">
            <div class="atlas-record-group-head">
                <h2 id="users-title" class="atlas-record-group-title">Users</h2>
            </div>

            <div class="atlas-record-figures">
                <dl class="log-facts" aria-label="Account totals">
                    @foreach ($userFigures as $figure)
                        <div>
                            <dt>{{ $figure['label'] }}</dt>
                            <dd class="is-numeric" data-metric="{{ $figure['metric'] }}">{{ $figure['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        <section class="atlas-record-group" aria-labelledby="curriculum-title">
            <div class="atlas-record-group-head">
                <h2 id="curriculum-title" class="atlas-record-group-title">Curriculum</h2>
            </div>

            <div class="atlas-record-figures">
                <dl class="log-facts" aria-label="Curriculum totals">
                    @foreach ($curriculumFigures as $figure)
                        <div>
                            <dt>{{ $figure['label'] }}</dt>
                            <dd class="is-numeric" data-metric="{{ $figure['metric'] }}">{{ $figure['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        <section class="atlas-record-group" aria-labelledby="learning-title">
            <div class="atlas-record-group-head">
                <h2 id="learning-title" class="atlas-record-group-title">Learning</h2>
            </div>

            <div class="atlas-record-figures">
                <dl class="log-facts" aria-label="Learning totals">
                    @foreach ($learningFigures as $figure)
                        <div>
                            <dt>{{ $figure['label'] }}</dt>
                            <dd class="is-numeric" data-metric="{{ $figure['metric'] }}">{{ $figure['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        <section class="atlas-record-group" aria-labelledby="per-course-title">
            <div class="atlas-record-group-head">
                <h2 id="per-course-title" class="atlas-record-group-title">Per-course analysis</h2>
                <p class="atlas-record-group-figure">Reused from course analytics</p>
            </div>

            @if ($courses->isEmpty())
                <section class="atlas-records-empty" aria-labelledby="empty-courses-title">
                    <h3 id="empty-courses-title" class="atlas-records-empty-title">No courses</h3>
                    <p class="atlas-records-empty-copy">No active course with missions is available to summarize.</p>
                </section>
            @else
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Course learning evidence</caption>
                        <thead>
                            <tr>
                                <th scope="col">Course</th>
                                <th scope="col">Engaged / Students</th>
                                <th scope="col">Completed</th>
                                <th scope="col">In progress</th>
                                <th scope="col">Ready</th>
                                <th scope="col">Avg completion</th>
                                <th scope="col">Pass rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($courses as $row)
                                <tr>
                                    <th scope="row" data-label="Course">{{ $row['course']->name }}</th>
                                    <td data-label="Engaged / Students" class="atlas-register-figure">{{ $row['engaged'] }}/{{ $row['fleet'] }}</td>
                                    <td data-label="Completed" data-course-metric="completed">{{ $row['buckets']['completed'] }}</td>
                                    <td data-label="In progress">{{ $row['buckets']['in_progress'] }}</td>
                                    <td data-label="Ready">{{ $row['buckets']['assessment_ready'] }}</td>
                                    <td data-label="Avg completion">{{ $row['avg_completion'] !== null ? $row['avg_completion'].'%' : '-' }}</td>
                                    <td data-label="Pass rate">{{ $row['pass_rate'] !== null ? $row['pass_rate'].'%' : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
