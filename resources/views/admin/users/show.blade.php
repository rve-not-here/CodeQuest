@extends('layouts.app', ['role' => $role])

@section('title', 'Account '.$user->username)

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Users · Platform scope</span>
            <a href="{{ route('admin.users') }}">← All users</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Account</span>
            <h1 class="atlas-records-title">{{ $user->username }}</h1>
            <p class="atlas-records-lede">{{ $user->name ?: 'No display name recorded.' }}</p>
            <dl class="log-facts atlas-records-facts" aria-label="Account record">
                <div><dt>Role</dt><dd>{{ ucfirst($user->role) }}</dd></div>
                <div><dt>Status</dt><dd>{{ $user->status === 'active' ? 'Active' : 'Inactive' }}</dd></div>
                <div><dt>Account created</dt><dd>{{ $user->created_at->format('M d, Y') }}</dd></div>
                <div><dt>Last activity</dt><dd>{{ $recentActivity->first() !== null ? $recentActivity->first()['at']->diffForHumans() : '—' }}</dd></div>
            </dl>
        </header>

        @if (session('status'))
            <div class="atlas-records-alerts">
                <div class="panel border-success/40 bg-success-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-success">Saved</strong>
                    <span class="text-fg-muted">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <div class="atlas-records-toolbar">
            <p class="atlas-records-subheading">Account record</p>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Edit account →</a>
        </div>

        <section class="atlas-record-group" aria-labelledby="recent-activity-title">
            <div class="atlas-record-group-head">
                <h2 id="recent-activity-title" class="atlas-record-group-title">Recent activity</h2>
                <p class="atlas-record-group-figure">{{ $recentActivity->count() }} {{ Str::plural('event', $recentActivity->count()) }}</p>
            </div>
            @if ($recentActivity->isEmpty())
                <p class="atlas-record-group-note">No learning activity on record.</p>
            @else
                <ol class="atlas-records-list">
                    @foreach ($recentActivity as $beat)
                        <li class="atlas-record-entry atlas-record-entry--compact">
                            <div class="atlas-record-entry-row">
                                <div>
                                    <p class="atlas-record-entry-line">{{ $beat['label'] }}</p>
                                    <p class="atlas-record-meta"><span>{{ $beat['type'] }}</span></p>
                                </div>
                                <time class="atlas-record-entry-figure" datetime="{{ $beat['at']->toIso8601String() }}">{{ $beat['at']->diffForHumans() }}</time>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
@endsection
