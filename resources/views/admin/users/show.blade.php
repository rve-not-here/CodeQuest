@extends('layouts.app', ['role' => $role])

@section('title', 'Account '.$user->username)

@section('content')
    <div class="admin-management">
    <nav class="admin-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('admin.users') }}">Users</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ $user->username }}</span>
    </nav>

    <x-page-header title="{{ $user->username }}" subtitle="Review account details, access, and recent activity.">
        <x-slot:actions>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn-primary">EDIT →</a>
            <a href="{{ route('admin.users') }}" class="btn-ghost">◀ ALL USERS</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    @php
        $roleTone = match ($user->role) {
            'admin' => 'cyan',
            'teacher' => 'amber',
            'operator' => 'dim',
            default => 'phosphor',
        };
        $statusTone = $user->status === 'active' ? 'cyan' : 'amber';
    @endphp

    <div class="admin-detail-layout">
        <x-panel title="Account">
            <dl class="admin-property-list">
                <div class="admin-property">
                    <dt>Username</dt>
                    <dd class="text-ink">{{ $user->username }}</dd>
                </div>
                <div class="admin-property">
                    <dt>Name</dt>
                    <dd class="text-ink">{{ $user->name ?: '—' }}</dd>
                </div>
                <div class="admin-property">
                    <dt>Role</dt>
                    <dd><x-badge tone="{{ $roleTone }}">{{ strtoupper($user->role) }}</x-badge></dd>
                </div>
                <div class="admin-property">
                    <dt>Status</dt>
                    <dd><x-badge tone="{{ $statusTone }}">{{ strtoupper($user->status) }}</x-badge></dd>
                </div>
                <div class="admin-property">
                    <dt>Account created</dt>
                    <dd class="text-ink">{{ $user->created_at->format('Y-m-d H:i') }}</dd>
                </div>
                <div class="admin-property">
                    <dt>Last activity</dt>
                    <dd class="text-ink">
                        @if ($recentActivity->isNotEmpty())
                            {{ $recentActivity->first()['label'] }}
                            <span class="block text-[13px] text-fg-muted">
                                {{ $recentActivity->first()['at']->diffForHumans() }}
                            </span>
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
        </x-panel>

        <x-panel title="Recent Activity">
            <div class="admin-beat-list">
                @forelse ($recentActivity as $beat)
                    <div class="admin-beat">
                        <p class="admin-beat-label">{{ $beat['label'] }}</p>
                        <p class="admin-beat-meta">{{ $beat['at']->diffForHumans() }} · {{ $beat['type'] }}</p>
                    </div>
                @empty
                    <x-status-message type="info" title="NO ACTIVITY">
                        No learning activity on record.
                    </x-status-message>
                @endforelse
            </div>
        </x-panel>
    </div>
    </div>
@endsection
