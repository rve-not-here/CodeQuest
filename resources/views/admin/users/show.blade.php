@extends('layouts.app', ['role' => $role])

@section('title', 'Account '.$user->username)

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Platform scope</span>
            <a href="{{ route('admin.users') }}">All users →</a>
        </div>

        <h1 class="sr-only">{{ $user->username }}</h1>

        @php
            $roleLabel = match ($user->role) {
                'admin' => 'Admin',
                'teacher' => 'Teacher',
                'operator' => 'Operator',
                default => 'Student',
            };
        @endphp

        <section class="atlas-record-figures" aria-label="Account record">
            <p class="atlas-records-lede">{{ $user->name ?: 'No display name recorded.' }}</p>
            <dl class="log-facts atlas-records-facts">
                <div>
                    <dt>Username</dt>
                    <dd class="font-mono">{{ $user->username }}</dd>
                </div>
                <div>
                    <dt>Role</dt>
                    <dd>{{ $roleLabel }}</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd>{{ $user->status === 'active' ? 'Active' : 'Inactive' }}</dd>
                </div>
                <div>
                    <dt>Created</dt>
                    <dd>{{ $user->created_at->format('M d, Y') }}</dd>
                </div>
            </dl>
        </section>

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

        @php
            $classrooms = $user->role === 'teacher'
                ? $user->teachingClassrooms
                : ($user->role === 'student' ? $user->enrolledClassrooms : collect());
        @endphp
        <section class="atlas-record-group" aria-labelledby="classroom-memberships-title">
            <div class="atlas-record-group-head">
                <h2 id="classroom-memberships-title" class="atlas-record-group-title">Classroom memberships</h2>
                <p class="atlas-record-group-figure">{{ $classrooms->count() }} {{ Str::plural('classroom', $classrooms->count()) }}</p>
            </div>
            @if ($classrooms->isEmpty())
                <p class="atlas-record-group-note">No classroom membership is recorded for this account.</p>
            @else
                <ul class="atlas-records-list" aria-label="Classroom memberships">
                    @foreach ($classrooms as $classroom)
                        <li class="atlas-record-entry atlas-record-entry--compact">
                            <div class="atlas-record-entry-row">
                                <div>
                                    <h3 class="atlas-record-entry-title">
                                        <a href="{{ route('admin.classrooms.edit', $classroom) }}">{{ $classroom->name }}</a>
                                    </h3>
                                    <p class="atlas-record-meta font-mono">{{ $classroom->code ?? 'No code' }}</p>
                                </div>
                                <span class="atlas-stamp {{ $classroom->status === 'active' ? 'atlas-stamp--grn' : 'atlas-stamp--ink' }}">{{ ucfirst($classroom->status) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

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
