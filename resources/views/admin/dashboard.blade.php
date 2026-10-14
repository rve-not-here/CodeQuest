@extends('layouts.app', ['role' => 'admin'])

@section('title', 'Admin Console')

@section('content')
    <div class="atlas-admin">
        <header class="admin-context">
            <div><h1>Admin Console</h1><p>Platform management and recorded system activity.</p></div>
            <span class="admin-state">SYSTEM-WIDE</span>
        </header>
        <div class="admin-console-layout">
            <div>
                <section class="admin-console-section" aria-labelledby="admin-users-title">
                    <header class="admin-section-heading"><h2 id="admin-users-title">Users</h2><a href="{{ route('admin.users') }}" class="btn-ghost">Manage users →</a></header>
                    <dl class="admin-counts">
                        @foreach (['users_total' => 'Total accounts', 'users_active' => 'Active', 'users_inactive' => 'Inactive'] as $key => $label)
                            <div><dt>{{ $label }}</dt><dd data-metric="{{ $key }}">{{ $metrics[$key] }}</dd></div>
                        @endforeach
                    </dl>
                </section>
                <section class="admin-console-section" aria-labelledby="admin-classrooms-title">
                    <header class="admin-section-heading"><h2 id="admin-classrooms-title">Classrooms</h2><a href="{{ route('admin.classrooms') }}" class="btn-ghost">Manage classrooms →</a></header>
                    <p>Manage student enrollment, teaching assignments and course access.</p>
                </section>
                <section class="admin-console-section" aria-labelledby="admin-curriculum-title">
                    <header class="admin-section-heading"><h2 id="admin-curriculum-title">Curriculum</h2><a href="{{ route('admin.courses') }}" class="btn-ghost">Manage curriculum →</a></header>
                    <dl class="admin-counts">
                        @foreach (['courses' => 'Courses', 'sections' => 'Sections', 'challenges' => 'Missions', 'boss_challenges' => 'Boss Challenges'] as $key => $label)
                            <div><dt>{{ $label }}</dt><dd data-metric="{{ $key }}">{{ $metrics[$key] }}</dd></div>
                        @endforeach
                    </dl>
                    <p class="admin-note">Choose a course to manage its sections, missions and Boss Challenge.</p>
                    <h3>Assessments</h3>
                    <dl class="admin-record-counts">
                        <div><dt>Recorded attempts</dt><dd data-metric="attempts">{{ $metrics['attempts'] }}</dd></div>
                        <div><dt>Passed attempts</dt><dd data-metric="passed_attempts">{{ $metrics['passed_attempts'] }}</dd></div>
                    </dl>
                </section>
                <section class="admin-console-section" aria-labelledby="admin-operations-title">
                    <h2 id="admin-operations-title">System operations</h2>
                    <nav class="admin-operations-links" aria-label="System operations">
                        <a href="{{ route('admin.announcements') }}">Announcements →</a>
                        <a href="{{ route('admin.activity') }}">Audit trail →</a>
                        <a href="{{ route('admin.analytics') }}">System analytics →</a>
                        <a href="{{ route('admin.system') }}">System status →</a>
                    </nav>
                    <h3>Learning records</h3>
                    <dl class="admin-record-counts">
                        <div><dt>Active students (14d)</dt><dd data-metric="active_students">{{ $metrics['active_students'] }}</dd></div>
                        <div><dt>Course completions</dt><dd data-metric="course_completions">{{ $metrics['course_completions'] }}</dd></div>
                    </dl>
                    <p class="admin-note">Learning and assessment results are read-only evidence. System analytics provides the supporting records.</p>
                </section>
            </div>
            <section class="admin-audit" aria-labelledby="admin-activity-title">
                <header class="admin-section-heading"><h2 id="admin-activity-title">Recent System Activity</h2><a href="{{ route('admin.activity') }}" class="btn-ghost">View audit trail →</a></header>
                @if ($recentSystemActivity->isEmpty())
                    <p class="admin-empty">No administrative actions have been recorded yet.</p>
                @else
                    <table class="table-stack admin-audit-table" role="table">
                        <caption>Recent administrative actions</caption>
                        <thead><tr><th scope="col">Actor</th><th scope="col">Recorded action</th><th scope="col">Result</th><th scope="col">Recorded at</th></tr></thead>
                        <tbody>
                            @foreach ($recentSystemActivity as $entry)
                                <tr>
                                    <th scope="row" data-label="Actor"><span class="admin-state">{{ $entry->admin_username }}</span></th>
                                    <td data-label="Recorded action"><span class="admin-state">{{ $entry->action }}</span><p class="admin-audit-summary">{{ $entry->summary }}</p>@if ($entry->target_type !== null)<span class="admin-state">{{ $entry->target_type }}@if ($entry->target_id !== null) #{{ $entry->target_id }}@endif</span>@endif</td>
                                    <td data-label="Result"><span class="admin-state admin-audit-result" data-result="{{ $entry->result }}">{{ strtoupper($entry->result) }}</span></td>
                                    <td data-label="Recorded at"><time class="admin-state" datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('Y-m-d H:i') }}</time></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        </div>
    </div>
@endsection
