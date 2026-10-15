@extends('layouts.app', ['role' => $role])

@section('title', 'Account '.$user->username)

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>{{ $user->username }}</h1><p>Review account details, access, and recent activity.</p></div>
        <div class="admin-actions"><a href="{{ route('admin.users') }}" class="btn-ghost">◀ ALL USERS</a>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn-ghost">EDIT →</a></div>
    </header>

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
        <section class="admin-record-section">
            <h2 class="panel-title mb-3">Account</h2>
            <dl class="admin-property-list">
                <div>
                    <dt class="text-xs font-bold text-fg-muted mb-1">Username</dt>
                    <dd class="text-ink">{{ $user->username }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-fg-muted mb-1">Name</dt>
                    <dd class="text-ink">{{ $user->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-fg-muted mb-1">Role</dt>
                    <dd class="mt-1"><x-badge tone="{{ $roleTone }}">{{ strtoupper($user->role) }}</x-badge></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-fg-muted mb-1">Status</dt>
                    <dd class="mt-1"><x-badge tone="{{ $statusTone }}">{{ strtoupper($user->status) }}</x-badge></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-fg-muted mb-1">Account created</dt>
                    <dd class="text-ink">{{ $user->created_at->format('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-fg-muted mb-1">Last activity</dt>
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
        </section>

        <section class="admin-record-section">
            <h2 class="panel-title mb-3">Recent Activity</h2>

            @forelse ($recentActivity as $beat)
                <div class="border-b border-fg-muted/40 py-1 last:border-0">
                    <p class="font-body text-[15px] text-ink leading-snug">{{ $beat['label'] }}</p>
                    <p class="text-xs font-bold text-fg-muted mt-0.5">
                        {{ $beat['at']->diffForHumans() }} · {{ $beat['type'] }}
                    </p>
                </div>
            @empty
                <p class="font-body text-[15px] text-ink">No learning activity on record.</p>
            @endforelse
        </section>
    </div>
    </div>
@endsection
