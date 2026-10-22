@extends('layouts.app', ['role' => 'admin'])

@section('title', 'Admin console')

@section('content')
    <div class="atlas-records admin-workspace admin-workspace-dashboard">
        <div class="log-running atlas-records-running">
            <span>Records · Platform scope</span>
        </div>

        <h1 class="sr-only">Admin console</h1>

        <dl class="log-facts atlas-records-facts" aria-label="Platform scope">
                <div><dt>Accounts</dt><dd class="is-numeric" data-metric="users_total">{{ $metrics['users_total'] }}</dd></div>
                <div><dt>Courses</dt><dd class="is-numeric" data-metric="courses">{{ $metrics['courses'] }}</dd></div>
                <div><dt>Challenges</dt><dd class="is-numeric" data-metric="challenges">{{ $metrics['challenges'] }}</dd></div>
                <div><dt>Recorded attempts</dt><dd class="is-numeric" data-metric="attempts">{{ $metrics['attempts'] }}</dd></div>
            </dl>


        <div class="admin-workspace-register" role="group" aria-label="Platform registers">
            <section class="atlas-record-group admin-workspace-panel" aria-labelledby="admin-users-title">
                <div class="atlas-record-group-head">
                    <h2 id="admin-users-title" class="atlas-record-group-title">Users</h2>
                    <p class="atlas-record-group-figure">Accounts and access</p>
                </div>
                <article class="atlas-record-entry">
                    <p class="atlas-record-entry-copy">Create accounts, assign roles, and manage access.</p>
                    <dl class="log-facts atlas-record-entry-facts">
                        <div><dt>Active</dt><dd class="is-numeric" data-metric="users_active">{{ $metrics['users_active'] }}</dd></div>
                        <div><dt>Inactive</dt><dd class="is-numeric" data-metric="users_inactive">{{ $metrics['users_inactive'] }}</dd></div>
                    </dl>
                    <div class="atlas-record-entry-actions">
                        <a href="{{ route('admin.users') }}" class="admin-workspace-link">Manage users →</a>
                    </div>
                </article>
            </section>

            <section class="atlas-record-group admin-workspace-panel" aria-labelledby="admin-curriculum-title">
                <div class="atlas-record-group-head">
                    <h2 id="admin-curriculum-title" class="atlas-record-group-title">Curriculum</h2>
                    <p class="atlas-record-group-figure">Courses and challenges</p>
                </div>
                <article class="atlas-record-entry">
                    <p class="atlas-record-entry-copy">Maintain courses, sections, challenges, and Boss Challenges.</p>
                    <dl class="log-facts atlas-record-entry-facts">
                        <div><dt>Sections</dt><dd class="is-numeric" data-metric="sections">{{ $metrics['sections'] }}</dd></div>
                        <div><dt>Boss Challenges</dt><dd class="is-numeric" data-metric="boss_challenges">{{ $metrics['boss_challenges'] }}</dd></div>
                    </dl>
                    <div class="atlas-record-entry-actions">
                        <a href="{{ route('admin.courses') }}" class="admin-workspace-link">Manage curriculum →</a>
                    </div>
                </article>
            </section>

            <section class="atlas-record-group admin-workspace-panel admin-workspace-panel--quiet" aria-labelledby="admin-assessments-title">
                <div class="atlas-record-group-head">
                    <h2 id="admin-assessments-title" class="atlas-record-group-title">Assessments</h2>
                    <p class="atlas-record-group-figure">Recorded evidence</p>
                </div>
                <article class="atlas-record-entry">
                    <p class="atlas-record-entry-copy">Review recorded Boss Challenge results and pass totals.</p>
                    <dl class="log-facts atlas-record-entry-facts">
                        <div><dt>Passed attempts</dt><dd class="is-numeric" data-metric="passed_attempts">{{ $metrics['passed_attempts'] }}</dd></div>
                    </dl>
                    <div class="atlas-record-entry-actions">
                        <a href="{{ route('admin.analytics') }}" class="admin-workspace-link">Review analytics →</a>
                    </div>
                </article>
            </section>

            <section class="atlas-record-group admin-workspace-panel admin-workspace-panel--quiet" aria-labelledby="admin-learning-title">
                <div class="atlas-record-group-head">
                    <h2 id="admin-learning-title" class="atlas-record-group-title">Learning</h2>
                    <p class="atlas-record-group-figure">Progress across the platform</p>
                </div>
                <article class="atlas-record-entry">
                    <p class="atlas-record-entry-copy">Active students had learning activity in the last 14 days. Course completion requires a passed Boss Challenge.</p>
                    <dl class="log-facts atlas-record-entry-facts">
                        <div><dt>Active students · 14d</dt><dd class="is-numeric" data-metric="active_students">{{ $metrics['active_students'] }}</dd></div>
                        <div><dt>Course completions</dt><dd class="is-numeric" data-metric="course_completions">{{ $metrics['course_completions'] }}</dd></div>
                    </dl>
                </article>
            </section>

        </div>

        <section class="atlas-record-group admin-workspace-operations" aria-labelledby="admin-operations-title">
            <div class="atlas-record-group-head">
                <h2 id="admin-operations-title" class="atlas-record-group-title">Operations</h2>
            </div>
            <article class="atlas-record-entry">
                <nav class="atlas-record-entry-actions" aria-label="Platform operations">
                    <a href="{{ route('admin.classrooms') }}" class="admin-workspace-link admin-workspace-link--row">Classrooms →</a>
                    <a href="{{ route('admin.announcements') }}" class="admin-workspace-link admin-workspace-link--row">Announcements →</a>
                    <a href="{{ route('admin.system') }}" class="admin-workspace-link admin-workspace-link--row">System status →</a>
                </nav>
            </article>
        </section>

        <section class="atlas-record-group admin-workspace-activity" aria-labelledby="admin-activity-title">
            <div class="atlas-record-group-head">
                <h2 id="admin-activity-title" class="atlas-record-group-title">Recent system activity</h2>
                <a href="{{ route('admin.activity') }}" class="admin-workspace-link admin-workspace-audit-link">View audit trail →</a>
            </div>
            @if ($recentSystemActivity->isEmpty())
                <div class="atlas-records-empty">
                    <p>No administrative actions have been recorded yet.</p>
                </div>
            @else
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
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
                                    <th scope="row" data-label="Actor">{{ $entry->admin_username }}</th>
                                    <td data-label="Recorded action">
                                        <span class="atlas-record-key">{{ $entry->action }}</span>
                                        <p class="mt-0.5">{{ $entry->summary }}</p>
                                        @if ($entry->target_type !== null)
                                            <span class="atlas-record-key">{{ $entry->target_type }}@if ($entry->target_id !== null) #{{ $entry->target_id }}@endif</span>
                                        @endif
                                    </td>
                                    <td data-label="Result">
                                        <span class="atlas-stamp {{ $entry->result === 'success' ? 'atlas-stamp--grn' : 'atlas-stamp--acc' }}"><x-icon :name="$entry->result === 'success' ? 'check' : 'x'" size="12" />{{ $entry->result === 'success' ? 'Success' : 'Failed' }}</span>
                                    </td>
                                    <td data-label="Recorded at"><time class="atlas-record-key" datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('M d, Y · H:i') }}</time></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
