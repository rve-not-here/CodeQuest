@extends('layouts.app', ['role' => $role])

@section('title', 'Audit Trail')

@section('content')
    <x-page-header
        title="Administrative Audit Trail"
        subtitle="Every mutation across the fleet, recorded by AdminAuditService::record — the only writer to the append-only ledger. Server-side filter on who acted, what action, result, and window."
    >
        <x-slot:actions>
            <x-badge tone="cyan">APPEND-ONLY</x-badge>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('admin.activity') }}" class="panel p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label for="actor" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Actor
                </label>
                <select id="actor" name="actor" class="terminal-input">
                    <option value="">ALL ACTORS</option>
                    @foreach ($filterActors as $actor)
                        <option
                            value="{{ $actor->id }}"
                            @selected(($filters['actor'] ?? null) == $actor->id)
                        >{{ $actor->username }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="action" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Action
                </label>
                <select id="action" name="action" class="terminal-input">
                    <option value="">ALL ACTIONS</option>
                    @foreach ($filterActions as $action)
                        <option
                            value="{{ $action }}"
                            @selected(($filters['action'] ?? null) === $action)
                        >{{ strtoupper(str_replace('.', ' · ', $action)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="result" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Result
                </label>
                <select id="result" name="result" class="terminal-input">
                    <option value="">ALL RESULTS</option>
                    @foreach (['success', 'failed'] as $result)
                        <option
                            value="{{ $result }}"
                            @selected(($filters['result'] ?? null) === $result)
                        >{{ strtoupper($result) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="from" class="block text-sm font-bold text-phosphor-dim mb-1">
                        From
                    </label>
                    <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="terminal-input">
                </div>
                <div>
                    <label for="to" class="block text-sm font-bold text-phosphor-dim mb-1">
                        To
                    </label>
                    <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="terminal-input">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn-ghost">FILTER →</button>
                @if (! empty($filters['actor']) || ! empty($filters['action']) || ! empty($filters['result']) || ($filters['from'] ?? '') !== '' || ($filters['to'] ?? '') !== '')
                    <a href="{{ route('admin.activity') }}" class="btn-ghost">CLEAR</a>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <x-status-message type="error" title="QUERY REJECTED" class="mt-3">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </x-status-message>
        @endif
    </form>

    <x-panel title="EVENT LOG">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <p class="text-sm font-bold text-phosphor-dim">
                WHO · WHAT · WHEN · TARGET · RESULT
            </p>
            <x-badge tone="dim">{{ $events->total() }} EVENTS</x-badge>
        </div>

        @if ($events->isEmpty())
            <x-status-message type="info" title="NO EVENTS">
                No audit trail entries match the current scan parameters.
            </x-status-message>
        @else
            <div class="divide-y divide-phosphor-dim/40">
                @foreach ($events as $event)
                    @php
                        $label = match ($event->action) {
                            \App\Services\AdminAuditService::ACTION_USER_CREATE => 'USER CREATED',
                            \App\Services\AdminAuditService::ACTION_USER_UPDATE => 'USER UPDATED',
                            \App\Services\AdminAuditService::ACTION_ROLE_CHANGE => 'ROLE CHANGE',
                            \App\Services\AdminAuditService::ACTION_STATUS_CHANGE => 'STATUS CHANGE',
                            \App\Services\AdminAuditService::ACTION_COURSE_UPDATE => 'COURSE UPDATE',
                            \App\Services\AdminAuditService::ACTION_COURSE_STATUS_CHANGE => 'COURSE STATUS CHANGE',
                            \App\Services\AdminAuditService::ACTION_SECTION_UPDATE => 'SECTION UPDATE',
                            \App\Services\AdminAuditService::ACTION_MISSION_UPDATE => 'MISSION UPDATE',
                            \App\Services\AdminAuditService::ACTION_ASSESSMENT_UPDATE => 'ASSESSMENT UPDATE',
                            \App\Services\AdminAuditService::ACTION_ASSESSMENT_STATUS_CHANGE => 'ASSESSMENT STATUS CHANGE',
                            default => strtoupper(str_replace('.', ' ', $event->action)),
                        };
                    @endphp
                    <div class="flex items-center gap-3 py-2">
                        <span class="text-sm font-bold text-phosphor shrink-0 w-8">
                            {{ $label }}
                        </span>
                        <span class="text-xs font-bold text-phosphor-dim shrink-0">
                            {{ $event->admin_username }}
                        </span>
                        <span class="font-body text-[15px] text-ink truncate min-w-0">{{ $event->summary }}</span>
                        <x-badge tone="{{ $event->result === 'failed' ? 'alert' : 'phosphor' }}">
                            {{ strtoupper($event->result) }}
                        </x-badge>
                        @if ($event->target_type !== null)
                            <x-badge tone="dim">#{{ $event->target_id }} {{ strtoupper($event->target_type) }}</x-badge>
                        @endif
                        <span class="text-xs font-bold text-phosphor-dim shrink-0 ml-auto">
                            {{ $event->created_at->format('M d, H:i') }}
                        </span>
                    </div>
                @endforeach
            </div>

            @if ($events->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-4 gap-y-2 p-3 border-t border-phosphor-dim/60">
                    <p class="text-sm font-bold text-phosphor-dim">
                        SHOWING PAGE {{ $events->currentPage() }} OF {{ $events->lastPage() }}
                    </p>
                    <div class="flex items-center gap-2">
                        @if ($events->onFirstPage())
                            <span class="btn-ghost opacity-50 pointer-events-none">◀ PREV</span>
                        @else
                            <a href="{{ $events->previousPageUrl() }}" class="btn-ghost">◀ PREV</a>
                        @endif

                        @if ($events->hasMorePages())
                            <a href="{{ $events->nextPageUrl() }}" class="btn-ghost">NEXT ▶</a>
                        @else
                            <span class="btn-ghost opacity-50 pointer-events-none">NEXT ▶</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </x-panel>
@endsection