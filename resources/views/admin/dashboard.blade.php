@extends('layouts.app', ['role' => 'admin'])

@section('title', 'Admin console')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Admin console · Platform scope</span>
            <a href="{{ route('admin.activity') }}">Audit trail →</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Operations</span>
            <h1 class="atlas-records-title">Admin console</h1>
            <p class="atlas-records-lede">Platform management and recorded system activity. Learning and assessment results are read-only evidence.</p>
            <dl class="log-facts atlas-records-facts" aria-label="Platform scope">
                <div><dt>Accounts</dt><dd class="is-numeric" data-metric="users_total">{{ $metrics['users_total'] }}</dd></div>
                <div><dt>Courses</dt><dd class="is-numeric" data-metric="courses">{{ $metrics['courses'] }}</dd></div>
                <div><dt>Challenges</dt><dd class="is-numeric" data-metric="challenges">{{ $metrics['challenges'] }}</dd></div>
                <div><dt>Recorded attempts</dt><dd class="is-numeric" data-metric="attempts">{{ $metrics['attempts'] }}</dd></div>
            </dl>
        </header>

        <section class="atlas-record-group" aria-labelledby="admin-users-title">
            <div class="atlas-record-group-head">
                <h2 id="admin-users-title" class="atlas-record-group-title">Users</h2>
                <p class="atlas-record-group-figure">Accounts and access</p>
            </div>
            <article class="atlas-record-entry">
                <h3 class="atlas-record-entry-title">Account register</h3>
                <p class="atlas-record-entry-copy">Create accounts, change roles and deactivate access. Every change is recorded in the audit trail.</p>
                <dl class="log-facts atlas-record-entry-facts">
                    <div><dt>Active</dt><dd class="is-numeric" data-metric="users_active">{{ $metrics['users_active'] }}</dd></div>
                    <div><dt>Inactive</dt><dd class="is-numeric" data-metric="users_inactive">{{ $metrics['users_inactive'] }}</dd></div>
                </dl>
                <div class="atlas-record-entry-actions">
                    <a href="{{ route('admin.users') }}" class="btn btn-primary">Manage users →</a>
                </div>
            </article>
        </section>

        <section class="atlas-record-group" aria-labelledby="admin-curriculum-title">
            <div class="atlas-record-group-head">
                <h2 id="admin-curriculum-title" class="atlas-record-group-title">Curriculum</h2>
                <p class="atlas-record-group-figure">Courses, sections and challenges</p>
            </div>
            <article class="atlas-record-entry">
                <h3 class="atlas-record-entry-title">Curriculum register</h3>
                <p class="atlas-record-entry-copy">Courses carry sections, sections carry challenges, and each course can end in one Boss Challenge.</p>
                <dl class="log-facts atlas-record-entry-facts">
                    <div><dt>Sections</dt><dd class="is-numeric" data-metric="sections">{{ $metrics['sections'] }}</dd></div>
                    <div><dt>Boss Challenges</dt><dd class="is-numeric" data-metric="boss_challenges">{{ $metrics['boss_challenges'] }}</dd></div>
                </dl>
                <div class="atlas-record-entry-actions">
                    <a href="{{ route('admin.courses') }}" class="btn btn-primary">Manage curriculum →</a>
                </div>
            </article>
        </section>

        <section class="atlas-record-group" aria-labelledby="admin-assessments-title">
            <div class="atlas-record-group-head">
                <h2 id="admin-assessments-title" class="atlas-record-group-title">Assessments</h2>
                <p class="atlas-record-group-figure">Recorded Boss Challenge evidence</p>
            </div>
            <article class="atlas-record-entry">
                <h3 class="atlas-record-entry-title">Attempt register</h3>
                <p class="atlas-record-entry-copy">Assessment results are read-only evidence. System analytics carries the supporting records.</p>
                <dl class="log-facts atlas-record-entry-facts">
                    <div><dt>Passed attempts</dt><dd class="is-numeric" data-metric="passed_attempts">{{ $metrics['passed_attempts'] }}</dd></div>
                </dl>
                <div class="atlas-record-entry-actions">
                    <a href="{{ route('admin.analytics') }}" class="btn-ghost">System analytics →</a>
                </div>
            </article>
        </section>

        <section class="atlas-record-group" aria-labelledby="admin-learning-title">
            <div class="atlas-record-group-head">
                <h2 id="admin-learning-title" class="atlas-record-group-title">Learning</h2>
                <p class="atlas-record-group-figure">Progress across the platform</p>
            </div>
            <article class="atlas-record-entry">
                <h3 class="atlas-record-entry-title">Learning register</h3>
                <p class="atlas-record-entry-copy">Active students are those with recorded learning activity in the last 14 days. A completion is a course whose Boss Challenge was passed.</p>
                <dl class="log-facts atlas-record-entry-facts">
                    <div><dt>Active students · 14d</dt><dd class="is-numeric" data-metric="active_students">{{ $metrics['active_students'] }}</dd></div>
                    <div><dt>Course completions</dt><dd class="is-numeric" data-metric="course_completions">{{ $metrics['course_completions'] }}</dd></div>
                </dl>
            </article>
        </section>

        <section class="atlas-record-group" aria-labelledby="admin-operations-title">
            <div class="atlas-record-group-head">
                <h2 id="admin-operations-title" class="atlas-record-group-title">Operations</h2>
            </div>
            <article class="atlas-record-entry">
                <h3 class="atlas-record-entry-title">Platform operations</h3>
                <p class="atlas-record-entry-copy">Classroom enrollment, announcements, the audit trail, analytics and system status live here.</p>
                <nav class="atlas-record-entry-actions" aria-label="Platform operations">
                    <a href="{{ route('admin.classrooms') }}" class="btn-ghost">Classrooms →</a>
                    <a href="{{ route('admin.announcements') }}" class="btn-ghost">Announcements →</a>
                    <a href="{{ route('admin.activity') }}" class="btn-ghost">Audit trail →</a>
                    <a href="{{ route('admin.analytics') }}" class="btn-ghost">System analytics →</a>
                    <a href="{{ route('reports.admin-system') }}" class="btn-ghost">System report →</a>
                    <a href="{{ route('admin.system') }}" class="btn-ghost">System status →</a>
                </nav>
            </article>
        </section>

        <section class="atlas-record-group" aria-labelledby="admin-activity-title">
            <div class="atlas-record-group-head">
                <h2 id="admin-activity-title" class="atlas-record-group-title">Recent system activity</h2>
                <a href="{{ route('admin.activity') }}" class="atlas-record-group-figure">View audit trail →</a>
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
                                        <span class="atlas-stamp {{ $entry->result === 'success' ? 'atlas-stamp--grn' : 'atlas-stamp--acc' }}">{{ $entry->result === 'success' ? 'Success' : 'Failed' }}</span>
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
