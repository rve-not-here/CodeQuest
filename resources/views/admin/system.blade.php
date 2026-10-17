@extends('layouts.app', ['role' => $role])

@section('title', 'System Status')

@section('content')
    <div class="admin-management">
    <x-page-header title="System Status" subtitle="Operational health of the deployment: application build, live database connectivity, applied migrations, and storage/log writability. Checks are performed at request time; the page is strictly read-only.">
        <x-slot:actions>
            <x-badge tone="cyan">SYSTEM-WIDE</x-badge>
            <x-badge tone="amber">READ-ONLY</x-badge>
        </x-slot:actions>
    </x-page-header>

    @php
        $migrationFigures = $migrations['readable'] ? [
            ['label' => 'Applied', 'value' => $migrations['applied'], 'metric' => 'migrations_applied'],
            ['label' => 'Latest batch', 'value' => $migrations['latest_batch'], 'metric' => 'migrations_latest_batch'],
        ] : null;
    @endphp

    <div class="admin-ledger">
        <x-panel title="Application">
            <x-slot:status><x-badge tone="phosphor">ONLINE</x-badge></x-slot:status>
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
        </x-panel>

        <x-panel title="Database">
            <x-slot:status>
                @if ($database['connected'])
                    <x-badge tone="phosphor">CONNECTED</x-badge>
                @else
                    <x-badge tone="alert">UNREACHABLE</x-badge>
                @endif
            </x-slot:status>
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
        </x-panel>

        <x-panel title="Migrations" :figures="$migrationFigures">
            <x-slot:status>
                @if ($migrations['readable'])
                    <x-badge tone="phosphor">READABLE</x-badge>
                @else
                    <x-badge tone="alert">UNREADABLE</x-badge>
                @endif
            </x-slot:status>
            @unless ($migrations['readable'])
                <p class="font-body text-[15px] leading-snug text-ink">The migrations table could not be read; migration status is unavailable.</p>
            @endunless
        </x-panel>

        <x-panel title="Storage & Logs">
            <x-slot:status>
                @if ($storage['logs_writable'])
                    <x-badge tone="phosphor">WRITABLE</x-badge>
                @else
                    <x-badge tone="alert">BLOCKED</x-badge>
                @endif
            </x-slot:status>
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
        </x-panel>
    </div>

    <x-status-message type="success" title="No credentials surfaced" class="mt-6">
        This page never exposes passwords, application keys, session secrets, or API keys. Every configuration value it reads passes an explicit allowlist inside SystemStatusService.
    </x-status-message>
    </div>
@endsection
