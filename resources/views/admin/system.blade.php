@extends('layouts.app', ['role' => $role])

@section('title', 'System status')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · System status · Platform scope</span>
            <span class="atlas-stamp atlas-stamp--ink"><x-icon name="eye" size="12" />Read-only</span>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Operations</span>
            <h1 class="atlas-records-title">System status</h1>
            <p class="atlas-records-lede">Operational health of the deployment: application build, live database connectivity, applied migrations and storage writability. Checks run at request time; the page is strictly read-only.</p>
        </header>

        <section class="atlas-record-group" aria-labelledby="application-title">
            <div class="atlas-record-group-head">
                <h2 id="application-title" class="atlas-record-group-title">Application</h2>
                <span class="atlas-stamp atlas-stamp--grn"><x-icon name="circle-check" size="12" />Online</span>
            </div>

            <div class="atlas-record-figures">
                <dl class="log-facts" aria-label="Application properties">
                    <div><dt>Environment</dt><dd>{{ ucfirst($application['environment']) }}</dd></div>
                    <div><dt>Debug mode</dt><dd>{{ $application['debug'] ? 'On' : 'Off' }}</dd></div>
                    <div><dt>Application</dt><dd>{{ $application['name'] }}</dd></div>
                    <div><dt>Laravel</dt><dd class="is-numeric" data-metric="laravel_version">{{ $application['laravel_version'] }}</dd></div>
                    <div><dt>PHP</dt><dd class="is-numeric" data-metric="php_version">{{ $application['php_version'] }}</dd></div>
                </dl>
            </div>
        </section>

        <section class="atlas-record-group" aria-labelledby="database-title">
            <div class="atlas-record-group-head">
                <h2 id="database-title" class="atlas-record-group-title">Database</h2>
                @if ($database['connected'])
                    <span class="atlas-stamp atlas-stamp--grn"><x-icon name="circle-check" size="12" />Connected</span>
                @else
                    <span class="atlas-stamp atlas-stamp--acc"><x-icon name="triangle-alert" size="12" />Unreachable</span>
                @endif
            </div>

            @if ($database['connected'])
                <div class="atlas-record-figures">
                    <dl class="log-facts" aria-label="Database properties">
                        <div><dt>Driver</dt><dd>{{ $database['driver'] }}</dd></div>
                        <div><dt>Server version</dt><dd>{{ $database['server_version'] ?? '-' }}</dd></div>
                    </dl>
                </div>
            @else
                <div class="atlas-callout" data-state="flagged">
                    <div>
                        <p class="atlas-callout-label">Database connection failed</p>
                        <p class="atlas-callout-copy">A live query failed. Application and log status below are still reported.</p>
                    </div>
                </div>
            @endif
        </section>

        <section class="atlas-record-group" aria-labelledby="migrations-title">
            <div class="atlas-record-group-head">
                <h2 id="migrations-title" class="atlas-record-group-title">Migrations</h2>
                @if ($migrations['readable'])
                    <span class="atlas-stamp atlas-stamp--grn"><x-icon name="circle-check" size="12" />Readable</span>
                @else
                    <span class="atlas-stamp atlas-stamp--acc"><x-icon name="triangle-alert" size="12" />Unreadable</span>
                @endif
            </div>

            @if ($migrations['readable'])
                <div class="atlas-record-figures">
                    <dl class="log-facts" aria-label="Migration properties">
                        <div><dt>Applied</dt><dd class="is-numeric" data-metric="migrations_applied">{{ $migrations['applied'] }}</dd></div>
                        <div><dt>Latest batch</dt><dd class="is-numeric" data-metric="migrations_latest_batch">{{ $migrations['latest_batch'] }}</dd></div>
                    </dl>
                </div>
            @else
                <p class="atlas-record-group-note">The migrations table could not be read; migration status is unavailable.</p>
            @endif
        </section>

        <section class="atlas-record-group" aria-labelledby="storage-title">
            <div class="atlas-record-group-head">
                <h2 id="storage-title" class="atlas-record-group-title">Storage &amp; logs</h2>
                @if ($storage['logs_writable'])
                    <span class="atlas-stamp atlas-stamp--grn"><x-icon name="circle-check" size="12" />Writable</span>
                @else
                    <span class="atlas-stamp atlas-stamp--acc"><x-icon name="lock" size="12" />Blocked</span>
                @endif
            </div>

            <div class="atlas-record-figures">
                <dl class="log-facts" aria-label="Storage properties">
                    <div><dt>Log channel</dt><dd>{{ $storage['log_channel'] }}</dd></div>
                    <div><dt>laravel.log</dt><dd>{{ $storage['log_file_exists'] ? 'Present' : 'Absent' }}</dd></div>
                    <div><dt>Last write</dt><dd>{{ $storage['log_file_last_modified'] ? \Illuminate\Support\Carbon::parse($storage['log_file_last_modified'])->diffForHumans() : '-' }}</dd></div>
                    <div><dt>Size</dt><dd class="is-numeric">{{ $storage['log_file_size'] !== null ? number_format($storage['log_file_size']).' bytes' : '-' }}</dd></div>
                </dl>
            </div>
        </section>

        <div class="atlas-callout" data-state="clear">
            <div>
                <p class="atlas-callout-label">No credentials surfaced</p>
                <p class="atlas-callout-copy">This page never exposes passwords, application keys, session secrets or API keys. Every configuration value it reads passes an explicit allowlist inside SystemStatusService.</p>
            </div>
            <span class="atlas-stamp atlas-stamp--ink"><x-icon name="shield-check" size="12" />Allowlisted</span>
        </div>
    </div>
@endsection
