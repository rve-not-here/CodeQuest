@extends('layouts.app', ['role' => $role])

@section('title', 'Audit trail')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Platform scope</span>
            <span class="atlas-stamp atlas-stamp--ink"><x-icon name="lock" size="12" />Append-only</span>
        </div>

        <h1 class="sr-only">Audit trail</h1>

        <section class="atlas-record-group" aria-labelledby="event-log-title">
            <div class="atlas-record-group-head">
                <h2 id="event-log-title" class="atlas-record-group-title">Event log</h2>
                <p class="atlas-record-group-figure">{{ $events->total() }} {{ Str::plural('event', $events->total()) }}</p>
            </div>

            <form method="GET" action="{{ route('admin.activity') }}" class="atlas-index-filters atlas-index-filters--wide" aria-label="Audit filters">
                <div class="atlas-index-filter">
                    <label for="actor">Actor</label>
                    <select id="actor" name="actor" class="terminal-input" @if ($errors->has('actor')) aria-invalid="true" aria-describedby="actor-error" @endif>
                        <option value="">All actors</option>
                        @foreach ($filterActors as $actor)
                            <option value="{{ $actor->id }}" @selected(($filters['actor'] ?? null) == $actor->id)>{{ $actor->username }}</option>
                        @endforeach
                    </select>
                    @error('actor')<p id="actor-error" class="atlas-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="atlas-index-filter">
                    <label for="action">Action</label>
                    <select id="action" name="action" class="terminal-input" @if ($errors->has('action')) aria-invalid="true" aria-describedby="action-error" @endif>
                        <option value="">All actions</option>
                        @foreach ($filterActions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? null) === $action)>{{ str_replace('.', ' · ', $action) }}</option>
                        @endforeach
                    </select>
                    @error('action')<p id="action-error" class="atlas-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="atlas-index-filter">
                    <label for="result">Result</label>
                    <select id="result" name="result" class="terminal-input" @if ($errors->has('result')) aria-invalid="true" aria-describedby="result-error" @endif>
                        <option value="">All results</option>
                        @foreach (['success', 'failed'] as $result)
                            <option value="{{ $result }}" @selected(($filters['result'] ?? null) === $result)>{{ ucfirst($result) }}</option>
                        @endforeach
                    </select>
                    @error('result')<p id="result-error" class="atlas-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="atlas-index-filter">
                    <label for="from">From</label>
                    <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="terminal-input" @if ($errors->has('from')) aria-invalid="true" aria-describedby="from-error" @endif>
                    @error('from')<p id="from-error" class="atlas-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="atlas-index-filter">
                    <label for="to">To</label>
                    <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="terminal-input" @if ($errors->has('to')) aria-invalid="true" aria-describedby="to-error" @endif>
                    @error('to')<p id="to-error" class="atlas-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="atlas-index-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply filters</button>
                    @if (! empty($filters['actor']) || ! empty($filters['action']) || ! empty($filters['result']) || ($filters['from'] ?? '') !== '' || ($filters['to'] ?? '') !== '')
                        <a href="{{ route('admin.activity') }}" class="btn btn-ghost">Reset filters</a>
                    @endif
                </div>
            </form>

            @if ($errors->any())
                <div class="atlas-index-alerts">
                    <div class="panel border-warning/40 bg-warning-soft px-4 py-3 text-sm" role="alert">
                        <strong class="text-warning">Query rejected</strong>
                        <span class="text-fg-muted">{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            @if ($events->isEmpty())
                <section class="atlas-records-empty" aria-labelledby="empty-events-title">
                    <h3 id="empty-events-title" class="atlas-records-empty-title">No events</h3>
                    <p class="atlas-records-empty-copy">No audit trail entries match these filters.</p>
                    <a href="{{ route('admin.activity') }}" class="btn btn-ghost">Reset filters →</a>
                </section>
            @else
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Administrative audit records</caption>
                        <thead>
                            <tr>
                                <th scope="col">Actor</th>
                                <th scope="col">Recorded action</th>
                                <th scope="col">Result</th>
                                <th scope="col">Target</th>
                                <th scope="col">Recorded at</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($events as $event)
                                @php
                                    $label = match ($event->action) {
                                        \App\Services\AdminAuditService::ACTION_USER_CREATE => 'User created',
                                        \App\Services\AdminAuditService::ACTION_USER_UPDATE => 'User updated',
                                        \App\Services\AdminAuditService::ACTION_ROLE_CHANGE => 'Role change',
                                        \App\Services\AdminAuditService::ACTION_STATUS_CHANGE => 'Status change',
                                        \App\Services\AdminAuditService::ACTION_COURSE_UPDATE => 'Course update',
                                        \App\Services\AdminAuditService::ACTION_COURSE_STATUS_CHANGE => 'Course status change',
                                        \App\Services\AdminAuditService::ACTION_SECTION_UPDATE => 'Section update',
                                        \App\Services\AdminAuditService::ACTION_MISSION_UPDATE => 'Challenge update',
                                        \App\Services\AdminAuditService::ACTION_ASSESSMENT_UPDATE => 'Assessment update',
                                        \App\Services\AdminAuditService::ACTION_ASSESSMENT_STATUS_CHANGE => 'Assessment status change',
                                        default => str_replace('.', ' · ', $event->action),
                                    };
                                @endphp

                                <tr>
                                    <th scope="row" data-label="Actor">{{ $event->admin_username }}</th>

                                    <td data-label="Recorded action">
                                        <span class="atlas-lesson-entry-label">{{ $label }}</span>
                                        @if (mb_strlen((string) $event->summary) > 220)
                                            <details class="atlas-audit-summary">
                                                <summary>{{ Str::limit($event->summary, 180) }}</summary>
                                                <p class="atlas-register-note">{{ $event->summary }}</p>
                                            </details>
                                        @else
                                            <p class="atlas-register-note">{{ $event->summary }}</p>
                                        @endif
                                    </td>

                                    <td data-label="Result">
                                        <span class="atlas-stamp {{ $event->result === 'success' ? 'atlas-stamp--grn' : 'atlas-stamp--acc' }}"><x-icon :name="$event->result === 'success' ? 'check' : 'x'" size="12" />{{ ucfirst($event->result) }}</span>
                                    </td>

                                    <td data-label="Target">
                                        @if ($event->target_type !== null)
                                            <span class="atlas-record-key">#{{ $event->target_id }} {{ $event->target_type }}</span>
                                        @else
                                            <span class="atlas-register-note">—</span>
                                        @endif
                                    </td>

                                    <td data-label="Recorded at">
                                        <time datetime="{{ $event->created_at->toIso8601String() }}" class="atlas-register-figure whitespace-nowrap">{{ $event->created_at->format('M d, Y · H:i') }}</time>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($events->hasPages())
                    <nav class="atlas-records-pager" aria-label="Audit trail pages">
                        <p class="atlas-records-pager-figure">Page {{ $events->currentPage() }} of {{ $events->lastPage() }}</p>
                        <div class="atlas-records-pager-actions">
                            @if ($events->onFirstPage())
                                <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">← Previous</span>
                            @else
                                <a href="{{ $events->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Previous</a>
                            @endif
                            @if ($events->hasMorePages())
                                <a href="{{ $events->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                            @else
                                <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">Next →</span>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </section>
    </div>
@endsection
