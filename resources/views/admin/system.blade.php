@extends('layouts.app', ['role' => $role])

@section('title', 'System Status')

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>System Status</h1><p>Operational health of the deployment: application build, live database connectivity, applied migrations, and storage/log writability. Checks are performed at request time; the page is strictly read-only.</p></div>
        <div class="admin-actions"><x-badge tone="cyan">SYSTEM-WIDE</x-badge>
            <x-badge tone="amber">READ-ONLY</x-badge></div>
    </header>

    <div class="admin-system-records">
        <section class="admin-system-section">
            <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-fg-muted/60">
                <h2 class="panel-title">Application</h2>
                <x-badge tone="phosphor">ONLINE</x-badge>
            </header>

            <dl class="admin-property-list">
                <div class="admin-property">
                    <dt class="text-fg-muted">Environment</dt>
                    <dd><x-badge tone="cyan">{{ mb_strtoupper($application['environment']) }}</x-badge></dd>
                </div>
                <div class="admin-property">
                    <dt class="text-fg-muted">Debug mode</dt>
                    <dd>{{ $application['debug'] ? 'ON' : 'OFF' }}</dd>
                </div>
                <div class="admin-property">
                    <dt class="text-fg-muted">Application</dt>
                    <dd>{{ $application['name'] }}</dd>
                </div>
                <div class="admin-property">
                    <dt class="text-fg-muted">Laravel</dt>
                    <dd data-metric="laravel_version">{{ $application['laravel_version'] }}</dd>
                </div>
                <div class="admin-property">
                    <dt class="text-fg-muted">PHP</dt>
                    <dd data-metric="php_version">{{ $application['php_version'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="admin-system-section">
            <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-fg-muted/60">
                <h2 class="panel-title">Database</h2>
                @if ($database['connected'])
                    <x-badge tone="phosphor">CONNECTED</x-badge>
                @else
                    <x-badge tone="alert">UNREACHABLE</x-badge>
                @endif
            </header>

            @if ($database['connected'])
                <dl class="admin-property-list">
                    <div class="admin-property">
                        <dt class="text-fg-muted">Driver</dt>
                        <dd><x-badge tone="cyan">{{ $database['driver'] }}</x-badge></dd>
                    </div>
                    <div class="admin-property">
                        <dt class="text-fg-muted">Server version</dt>
                        <dd>{{ $database['server_version'] ?? '-' }}</dd>
                    </div>
                </dl>
            @else
                <x-status-message type="error" title="Database connection failed">
                    A live query failed. Application and log status below are still reported.
                </x-status-message>
            @endif
        </section>
    </div>

    <div class="admin-system-records">
        <section class="admin-system-section">
            <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-fg-muted/60">
                <h2 class="panel-title">Migrations</h2>
                @if ($migrations['readable'])
                    <x-badge tone="phosphor">READABLE</x-badge>
                @else
                    <x-badge tone="alert">UNREADABLE</x-badge>
                @endif
            </header>

            @if ($migrations['readable'])
                <dl class="admin-property-list">
                    <div class="admin-property">
                        <dt class="text-fg-muted">Applied</dt>
                        <dd data-metric="migrations_applied" class="font-display text-ink">{{ $migrations['applied'] }}</dd>
                    </div>
                    <div class="admin-property">
                        <dt class="text-fg-muted">Latest batch</dt>
                        <dd data-metric="migrations_latest_batch" class="font-display text-ink">{{ $migrations['latest_batch'] }}</dd>
                    </div>
                </dl>
            @else
                <p class="font-body text-[15px] leading-snug text-ink">The migrations table could not be read; migration status is unavailable.</p>
            @endif
        </section>

        <section class="admin-system-section">
            <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-fg-muted/60">
                <h2 class="panel-title">Storage & Logs</h2>
                @if ($storage['logs_writable'])
                    <x-badge tone="phosphor">WRITABLE</x-badge>
                @else
                    <x-badge tone="alert">BLOCKED</x-badge>
                @endif
            </header>

            <dl class="admin-property-list">
                <div class="admin-property">
                    <dt class="text-fg-muted">Log channel</dt>
                    <dd><x-badge tone="cyan">{{ $storage['log_channel'] }}</x-badge></dd>
                </div>
                <div class="admin-property">
                    <dt class="text-fg-muted">laravel.log</dt>
                    <dd>{{ $storage['log_file_exists'] ? 'PRESENT' : 'ABSENT' }}</dd>
                </div>
                <div class="admin-property">
                    <dt class="text-fg-muted">Last write</dt>
                    <dd>{{ $storage['log_file_last_modified'] ? \Illuminate\Support\Carbon::parse($storage['log_file_last_modified'])->diffForHumans() : '-' }}</dd>
                </div>
                <div class="admin-property">
                    <dt class="text-fg-muted">Size</dt>
                    <dd>{{ $storage['log_file_size'] !== null ? number_format($storage['log_file_size']).' bytes' : '-' }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <section class="admin-system-section">
        <x-status-message type="success" title="No credentials surfaced">
            This page never exposes passwords, application keys, session secrets, or API keys. Every configuration value it reads passes an explicit allowlist inside SystemStatusService.
        </x-status-message>
    </section>
    </div>
@endsection
