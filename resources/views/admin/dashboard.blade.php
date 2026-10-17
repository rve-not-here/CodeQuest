@extends('layouts.app', ['role' => 'admin'])

@section('title', 'Admin Console')

@section('content')
    <x-page-header title="Admin Console" subtitle="Platform management and recorded system activity.">
        <x-slot:actions>
            <x-badge tone="cyan">SYSTEM-WIDE</x-badge>
            <x-badge tone="dim">READ-ONLY EVIDENCE</x-badge>
        </x-slot:actions>
    </x-page-header>

    @php
        $panelAction = fn (string $route, string $label): string => '<a href="'.route($route).'" class="panel-action">'.$label.' →</a>';

        $usersFigures = [
            ['label' => 'Total accounts', 'value' => $metrics['users_total'], 'metric' => 'users_total'],
            ['label' => 'Active', 'value' => $metrics['users_active'], 'metric' => 'users_active'],
            ['label' => 'Inactive', 'value' => $metrics['users_inactive'], 'metric' => 'users_inactive'],
        ];

        $curriculumFigures = [
            ['label' => 'Courses', 'value' => $metrics['courses'], 'metric' => 'courses'],
            ['label' => 'Sections', 'value' => $metrics['sections'], 'metric' => 'sections'],
            ['label' => 'Missions', 'value' => $metrics['challenges'], 'metric' => 'challenges'],
            ['label' => 'Boss Challenges', 'value' => $metrics['boss_challenges'], 'metric' => 'boss_challenges'],
        ];

        $assessmentFigures = [
            ['label' => 'Recorded attempts', 'value' => $metrics['attempts'], 'metric' => 'attempts'],
            ['label' => 'Passed attempts', 'value' => $metrics['passed_attempts'], 'metric' => 'passed_attempts'],
        ];

        $learningFigures = [
            ['label' => 'Active students (14d)', 'value' => $metrics['active_students'], 'metric' => 'active_students'],
            ['label' => 'Course completions', 'value' => $metrics['course_completions'], 'metric' => 'course_completions'],
        ];
    @endphp

    <div class="admin-console atlas-admin">
        <div class="admin-register">
            <x-panel title="Users" :actions="$panelAction('admin.users', 'Manage users')" :figures="$usersFigures" />

            <x-panel title="Curriculum" :actions="$panelAction('admin.courses', 'Manage curriculum')" :figures="$curriculumFigures" />

            <x-panel title="Assessments" :actions="$panelAction('admin.analytics', 'System analytics')" :figures="$assessmentFigures" />

            <x-panel title="Learning" :figures="$learningFigures" />
        </div>

        <x-panel title="Operations">
            <div class="admin-operations-grid">
                <div class="admin-operations-group">
                    <h3>Classrooms</h3>
                    <p class="admin-note">Manage student enrollment, teaching assignments and course access.</p>
                    <a href="{{ route('admin.classrooms') }}">Manage classrooms →</a>
                </div>

                <div class="admin-operations-group">
                    <h3>System operations</h3>
                    <nav class="admin-operations-links" aria-label="System operations">
                        <a href="{{ route('admin.announcements') }}">Announcements →</a>
                        <a href="{{ route('admin.activity') }}">Audit trail →</a>
                        <a href="{{ route('admin.analytics') }}">System analytics →</a>
                        <a href="{{ route('admin.system') }}">System status →</a>
                    </nav>
                </div>
            </div>

            <p class="admin-note admin-operations-note">Learning and assessment results are read-only evidence. System analytics provides the supporting records.</p>
        </x-panel>

        <x-panel title="Recent System Activity" :actions="$panelAction('admin.activity', 'View audit trail')">
            @if ($recentSystemActivity->isEmpty())
                <x-status-message type="info" title="NO ACTIVITY">
                    No administrative actions have been recorded yet.
                </x-status-message>
            @else
                <table class="table-stack admin-audit-table" role="table">
                    <caption>Recent administrative actions</caption>
                    <thead>
                        <tr>
                            <th scope="col">Actor</th>
                            <th scope="col">Recorded action</th>
                            <th scope="col">Result</th>
                            <th scope="col">Recorded at</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentSystemActivity as $entry)
                            <tr>
                                <th scope="row" data-label="Actor"><span class="admin-state">{{ $entry->admin_username }}</span></th>
                                <td data-label="Recorded action"><span class="admin-state">{{ $entry->action }}</span><p class="admin-audit-summary">{{ $entry->summary }}</p>@if ($entry->target_type !== null)<span class="admin-state">{{ $entry->target_type }}@if ($entry->target_id !== null) #{{ $entry->target_id }}@endif</span>@endif</td>
                                <td data-label="Result"><x-badge tone="{{ $entry->result === 'success' ? 'success' : 'alert' }}" class="whitespace-nowrap">{{ strtoupper($entry->result) }}</x-badge></td>
                                <td data-label="Recorded at"><time class="admin-state" datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('Y-m-d H:i') }}</time></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-panel>
    </div>
@endsection
