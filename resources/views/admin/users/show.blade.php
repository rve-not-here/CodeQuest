@extends('layouts.app', ['role' => $role])

@section('title', 'Account '.$user->username)

@section('content')
    <x-page-header
        title="{{ $user->username }}"
        subtitle="Account record. Role and status are server-assigned and guarded: no self-demotion, no self-deactivation, and the active-admin fleet never drops below two."
        icon="☷"
    >
        <x-slot:actions>
            <a href="{{ route('admin.users') }}" class="btn-ghost">◀ ALL USERS</a>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn-ghost">EDIT →</a>
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <section class="panel p-4 md:col-span-2">
            <h2 class="panel-title mb-3">Account</h2>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3 font-body text-lg">
                <div>
                    <dt class="text-xs font-bold text-phosphor-dim mb-1">Username</dt>
                    <dd class="text-ink">{{ $user->username }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-phosphor-dim mb-1">Name</dt>
                    <dd class="text-ink">{{ $user->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-phosphor-dim mb-1">Role</dt>
                    <dd class="mt-1"><x-badge tone="{{ $roleTone }}">{{ strtoupper($user->role) }}</x-badge></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-phosphor-dim mb-1">Status</dt>
                    <dd class="mt-1"><x-badge tone="{{ $statusTone }}">{{ strtoupper($user->status) }}</x-badge></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-phosphor-dim mb-1">Account created</dt>
                    <dd class="text-ink">{{ $user->created_at->format('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-phosphor-dim mb-1">Last activity</dt>
                    <dd class="text-ink">
                        @if ($recentActivity->isNotEmpty())
                            {{ $recentActivity->first()['label'] }}
                            <span class="block text-[13px] text-phosphor-dim">
                                {{ $recentActivity->first()['at']->diffForHumans() }}
                            </span>
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        <section class="panel p-4">
            <h2 class="panel-title mb-3">Recent Activity</h2>

            @forelse ($recentActivity as $beat)
                <div class="border-b border-phosphor-dim/40 py-1 last:border-0">
                    <p class="font-body text-[15px] text-ink leading-snug">{{ $beat['label'] }}</p>
                    <p class="text-xs font-bold text-phosphor-dim mt-0.5">
                        {{ $beat['at']->diffForHumans() }} · {{ $beat['type'] }}
                    </p>
                </div>
            @empty
                <p class="font-body text-[15px] text-ink">No learning activity on record.</p>
            @endforelse
        </section>
    </div>
@endsection