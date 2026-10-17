@extends('layouts.app', ['role' => $role])

@section('title', 'System Analytics')

@section('content')
    <div class="admin-management">
    <x-page-header title="System Analytics" subtitle="Recorded account, curriculum, learning, and XP evidence. Read-only, system-wide.">
        <x-slot:actions>
            <x-badge tone="cyan">SYSTEM-WIDE</x-badge>
            <x-badge tone="amber">READ-ONLY</x-badge>
        </x-slot:actions>
    </x-page-header>

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
            $systemFigure('challenges', 'Missions'),
            $systemFigure('boss_challenges', 'Assessments'),
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

    <div class="admin-ledger">
        <x-panel title="XP Administration" :figures="$xpFigures">
            <x-slot:status><x-badge tone="dim">VIEW ONLY</x-badge></x-slot:status>
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
        </x-panel>

        <x-panel title="Fleet by Role" :figures="$roleFigures" />

        <x-panel title="Users" :figures="$userFigures" />

        <x-panel title="Curriculum" :figures="$curriculumFigures" />

        <x-panel title="Learning" :figures="$learningFigures" />

        <x-panel title="Per-Course Analysis">
            <x-slot:status><x-badge tone="dim">REUSED FROM COURSE ANALYTICS</x-badge></x-slot:status>
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
        </x-panel>
    </div>
    </div>
@endsection
