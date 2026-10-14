@extends('layouts.app', ['role' => $role])

@section('title', 'System Analytics')

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>System Analytics</h1><p>Recorded account, curriculum, learning, and XP evidence. Read-only, system-wide.</p></div>
        <span class="admin-state">SYSTEM-WIDE · READ-ONLY</span>
    </header>

    <section class="admin-record-section" aria-labelledby="xp-heading">
        <div class="admin-section-heading"><h2 id="xp-heading">XP Administration</h2><span class="admin-state">VIEW ONLY</span></div>
        <dl class="admin-evidence-totals">
            @foreach ([
                'xp_awarded' => ['Awarded', $xp['awarded']],
                'xp_spent' => ['Spent', $xp['spent']],
                'xp_deducted' => ['Deducted', $xp['deducted']],
                'xp_outstanding' => ['Outstanding', $xp['outstanding']],
                'xp_accounts' => ['Accounts', $xp['accounts']],
            ] as $key => [$label, $value])
                <div><dt>{{ $label }}</dt><dd data-metric="{{ $key }}">{{ $value }}</dd></div>
            @endforeach
        </dl>
        @if ($xp['by_type'] === [])
            <x-status-message type="info" title="Empty ledger">No XP transactions have been recorded yet.</x-status-message>
        @else
            <table class="table-stack admin-table" role="table">
                <caption>XP ledger sources</caption>
                <thead><tr><th scope="col">Source</th><th scope="col">Direction</th><th scope="col">Entries</th><th scope="col">Total</th></tr></thead>
                <tbody>
                    @foreach ($xp['by_type'] as $row)
                        <tr>
                            <th scope="row" data-label="Source">{{ $row['label'] }}</th>
                            <td data-label="Direction">@if ($row['direction'] === 'award') CREDIT @elseif ($row['direction'] === 'spend') SPEND @else DEDUCT @endif</td>
                            <td data-label="Entries" data-metric="xp_entries_{{ $row['type'] }}">{{ $row['entries'] }}</td>
                            <td data-label="Total" data-metric="xp_total_{{ $row['type'] }}">{{ $row['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="admin-record-section"><h2>Fleet by Role</h2>
        <dl class="admin-evidence-totals">
            @foreach ([
                'role_student' => ['Students', $roles['student'] ?? 0],
                'role_teacher' => ['Teachers', $roles['teacher'] ?? 0],
                'role_admin' => ['Admins', $roles['admin'] ?? 0],
                'role_operator' => ['Operators', $roles['operator'] ?? 0],
            ] as $key => [$label, $value])
                <div><dt>{{ $label }}</dt><dd data-metric="{{ $key }}">{{ $value }}</dd></div>
            @endforeach
        </dl>
    </section>
    <section class="admin-record-section"><h2>Users</h2>
        <dl class="admin-evidence-totals">
            @foreach ([
                    'users_total' => 'Total',
                    'users_active' => 'Active',
                    'users_inactive' => 'Inactive',
                ] as $key => $label)
                <div><dt>{{ $label }}</dt><dd data-metric="{{ $key }}">{{ $system[$key] !== null ? $system[$key].($key === 'pass_rate' ? '%' : '') : '-' }}</dd></div>
            @endforeach
        </dl>
    </section>
    <section class="admin-record-section"><h2>Curriculum</h2>
        <dl class="admin-evidence-totals">
            @foreach ([
                    'courses' => 'Courses',
                    'sections' => 'Sections',
                    'challenges' => 'Missions',
                    'boss_challenges' => 'Assessments',
                ] as $key => $label)
                <div><dt>{{ $label }}</dt><dd data-metric="{{ $key }}">{{ $system[$key] !== null ? $system[$key].($key === 'pass_rate' ? '%' : '') : '-' }}</dd></div>
            @endforeach
        </dl>
    </section>
    <section class="admin-record-section"><h2>Learning</h2>
        <dl class="admin-evidence-totals">
            @foreach ([
                    'active_students' => 'Active students (14d)',
                    'completed_challenges' => 'Completed challenges',
                    'course_completions' => 'Course completions',
                    'attempts' => 'Assessment attempts',
                    'passed_attempts' => 'Attempts passed',
                    'pass_rate' => 'Assessment pass rate',
                ] as $key => $label)
                <div><dt>{{ $label }}</dt><dd data-metric="{{ $key }}">{{ $system[$key] !== null ? $system[$key].($key === 'pass_rate' ? '%' : '') : '-' }}</dd></div>
            @endforeach
        </dl>
    </section>
    <section class="admin-record-section" aria-labelledby="course-evidence-heading">
        <div class="admin-section-heading"><h2 id="course-evidence-heading">Per-Course Analysis</h2><span class="admin-state">REUSED FROM COURSE ANALYTICS</span></div>
        @if ($courses->isEmpty())
            <x-status-message type="info" title="No courses">No active course with missions is available to summarize.</x-status-message>
        @else
            <table class="table-stack admin-table" role="table">
                <caption>Course learning evidence</caption>
                <thead><tr><th scope="col">Course</th><th scope="col">Engaged / Students</th><th scope="col">Completed</th><th scope="col">In progress</th><th scope="col">Ready</th><th scope="col">Avg completion</th><th scope="col">Pass rate</th></tr></thead>
                <tbody>
                    @foreach ($courses as $row)
                        <tr>
                            <th scope="row" data-label="Course">{{ $row['course']->name }}</th>
                            <td data-label="Engaged / Students">{{ $row['engaged'] }}/{{ $row['fleet'] }}</td>
                            <td data-label="Completed" data-course-metric="completed">{{ $row['buckets']['completed'] }}</td>
                            <td data-label="In progress">{{ $row['buckets']['in_progress'] }}</td>
                            <td data-label="Ready">{{ $row['buckets']['assessment_ready'] }}</td>
                            <td data-label="Avg completion">{{ $row['avg_completion'] !== null ? $row['avg_completion'].'%' : '-' }}</td>
                            <td data-label="Pass rate">{{ $row['pass_rate'] !== null ? $row['pass_rate'].'%' : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
    </div>
@endsection
