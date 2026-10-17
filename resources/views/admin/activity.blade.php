@extends('layouts.app', ['role' => $role])

@section('title', 'Audit Trail')

@section('content')
    <div class="admin-management">
    <x-page-header title="Administrative Audit Trail" subtitle="Recorded administrative actions. Filter by actor, action, result, and date window.">
        <x-slot:actions>
            <x-badge tone="cyan">APPEND-ONLY</x-badge>
        </x-slot:actions>
    </x-page-header>

    <x-panel title="Event log">
        <x-slot:status><x-badge tone="dim">{{ $events->total() }} EVENTS</x-badge></x-slot:status>

        <form method="GET" action="{{ route('admin.activity') }}" class="admin-toolbar">
            <div class="admin-toolbar-field">
                <label for="actor">Actor</label>
                <select id="actor" name="actor" class="terminal-input" @if ($errors->has('actor')) aria-invalid="true" aria-describedby="actor-error" @endif>
                    <option value="">ALL ACTORS</option>
                    @foreach ($filterActors as $actor)
                        <option
                            value="{{ $actor->id }}"
                            @selected(($filters['actor'] ?? null) == $actor->id)
                        >{{ $actor->username }}</option>
                    @endforeach
                </select>
                @error('actor')<p id="actor-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="admin-toolbar-field">
                <label for="action">Action</label>
                <select id="action" name="action" class="terminal-input" @if ($errors->has('action')) aria-invalid="true" aria-describedby="action-error" @endif>
                    <option value="">ALL ACTIONS</option>
                    @foreach ($filterActions as $action)
                        <option
                            value="{{ $action }}"
                            @selected(($filters['action'] ?? null) === $action)
                        >{{ strtoupper(str_replace('.', ' · ', $action)) }}</option>
                    @endforeach
                </select>
                @error('action')<p id="action-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="admin-toolbar-field">
                <label for="result">Result</label>
                <select id="result" name="result" class="terminal-input" @if ($errors->has('result')) aria-invalid="true" aria-describedby="result-error" @endif>
                    <option value="">ALL RESULTS</option>
                    @foreach (['success', 'failed'] as $result)
                        <option
                            value="{{ $result }}"
                            @selected(($filters['result'] ?? null) === $result)
                        >{{ strtoupper($result) }}</option>
                    @endforeach
                </select>
                @error('result')<p id="result-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="admin-toolbar-field admin-toolbar-field--date">
                <label for="from">From</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="terminal-input" @if ($errors->has('from')) aria-invalid="true" aria-describedby="from-error" @endif>
                @error('from')<p id="from-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="admin-toolbar-field admin-toolbar-field--date">
                <label for="to">To</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="terminal-input" @if ($errors->has('to')) aria-invalid="true" aria-describedby="to-error" @endif>
                @error('to')<p id="to-error" class="admin-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="admin-toolbar-actions">
                <button type="submit" class="btn-ghost">FILTER →</button>
                @if (! empty($filters['actor']) || ! empty($filters['action']) || ! empty($filters['result']) || ($filters['from'] ?? '') !== '' || ($filters['to'] ?? '') !== '')
                    <a href="{{ route('admin.activity') }}" class="btn-ghost">CLEAR</a>
                @endif
            </div>
        </form>

        @if ($errors->any())
            <x-status-message type="error" title="QUERY REJECTED" class="mt-3">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </x-status-message>
        @endif

        @if ($events->isEmpty())
            <x-status-message type="info" title="NO EVENTS">No audit trail entries match these filters.</x-status-message>
        @else
            <table class="table-stack admin-table" role="table">
                <caption>Administrative audit records</caption>
                <thead><tr><th scope="col">Actor</th><th scope="col">Recorded action</th><th scope="col">Result</th><th scope="col">Target</th><th scope="col">Recorded at</th></tr></thead>
                <tbody>
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
                        <tr>
                            <th scope="row" data-label="Actor">{{ $event->admin_username }}</th>
                            <td data-label="Recorded action"><span class="admin-state">{{ $label }}</span><p>{{ $event->summary }}</p></td>
                            <td data-label="Result"><x-badge tone="{{ $event->result === 'success' ? 'success' : 'alert' }}">{{ strtoupper($event->result) }}</x-badge></td>
                            <td data-label="Target">@if ($event->target_type !== null)<span class="admin-state">#{{ $event->target_id }} {{ strtoupper($event->target_type) }}</span>@else — @endif</td>
                            <td data-label="Recorded at"><time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('Y-m-d H:i') }}</time></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($events->hasPages())
                <div class="panel-pagination">
                    <p>SHOWING PAGE {{ $events->currentPage() }} OF {{ $events->lastPage() }}</p>
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
    </div>
@endsection
