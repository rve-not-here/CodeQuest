@extends('layouts.app', ['role' => $role])

@section('title', 'System Status')

@section('content')
    <x-page-header
        title="System Status"
        subtitle="Operational health of the deployment: application build, live database connectivity, applied migrations, and storage/log writability. Checks are performed at request time; the page is strictly read-only."
    >
        <x-slot:actions>
            <x-badge tone="cyan">SYSTEM-WIDE</x-badge>
            <x-badge tone="amber">READ-ONLY</x-badge>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <section class="panel p-4">
            <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-phosphor-dim/60">
                <h2 class="panel-title">Application</h2>
                <x-badge tone="phosphor">ONLINE</x-badge>
            </header>

            <dl class="grid grid-cols-1 gap-2 text-[15px] font-body">
                <div class="flex items-center justify-between">
                    <dt class="text-phosphor-dim">Environment</dt>
                    <dd><x-badge tone="cyan">{{ mb_strtoupper($application['environment']) }}</x-badge></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-phosphor-dim">Debug mode</dt>
                    <dd>{{ $application['debug'] ? 'ON' : 'OFF' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-phosphor-dim">Application</dt>
                    <dd>{{ $application['name'] }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-phosphor-dim">Laravel</dt>
                    <dd data-metric="laravel_version">{{ $application['laravel_version'] }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-phosphor-dim">PHP</dt>
                    <dd data-metric="php_version">{{ $application['php_version'] }}</dd>
                </div>
            </dl>
        </section>

        <section class="panel p-4">
            <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-phosphor-dim/60">
                <h2 class="panel-title">Database</h2>
                @if ($database['connected'])
                    <x-badge tone="phosphor">CONNECTED</x-badge>
                @else
                    <x-badge tone="alert">UNREACHABLE</x-badge>
                @endif
            </header>

            @if ($database['connected'])
                <dl class="grid grid-cols-1 gap-2 text-[15px] font-body">
                    <div class="flex items-center justify-between">
                        <dt class="text-phosphor-dim">Driver</dt>
                        <dd><x-badge tone="cyan">{{ $database['driver'] }}</x-badge></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-phosphor-dim">Server version</dt>
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <section class="panel p-4">
            <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-phosphor-dim/60">
                <h2 class="panel-title">Migrations</h2>
                @if ($migrations['readable'])
                    <x-badge tone="phosphor">READABLE</x-badge>
                @else
                    <x-badge tone="alert">UNREADABLE</x-badge>
                @endif
            </header>

            @if ($migrations['readable'])
                <dl class="grid grid-cols-1 gap-2 text-[15px] font-body">
                    <div class="flex items-center justify-between">
                        <dt class="text-phosphor-dim">Applied</dt>
                        <dd data-metric="migrations_applied" class="font-display text-ink">{{ $migrations['applied'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-phosphor-dim">Latest batch</dt>
                        <dd data-metric="migrations_latest_batch" class="font-display text-ink">{{ $migrations['latest_batch'] }}</dd>
                    </div>
                </dl>
            @else
                <p class="font-body text-[15px] leading-snug text-ink">The migrations table could not be read; migration status is unavailable.</p>
            @endif
        </section>

        <section class="panel p-4">
            <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-phosphor-dim/60">
                <h2 class="panel-title">Storage & Logs</h2>
                @if ($storage['logs_writable'])
                    <x-badge tone="phosphor">WRITABLE</x-badge>
                @else
                    <x-badge tone="alert">BLOCKED</x-badge>
                @endif
            </header>

            <dl class="grid grid-cols-1 gap-2 text-[15px] font-body">
                <div class="flex items-center justify-between">
                    <dt class="text-phosphor-dim">Log channel</dt>
                    <dd><x-badge tone="cyan">{{ $storage['log_channel'] }}</x-badge></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-phosphor-dim">laravel.log</dt>
                    <dd>{{ $storage['log_file_exists'] ? 'PRESENT' : 'ABSENT' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-phosphor-dim">Last write</dt>
                    <dd>{{ $storage['log_file_last_modified'] ? \Illuminate\Support\Carbon::parse($storage['log_file_last_modified'])->diffForHumans() : '-' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-phosphor-dim">Size</dt>
                    <dd>{{ $storage['log_file_size'] !== null ? number_format($storage['log_file_size']).' bytes' : '-' }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <section class="panel p-4">
        <x-status-message type="success" title="No credentials surfaced">
            This page never exposes passwords, application keys, session secrets, or API keys. Every configuration value it reads passes an explicit allowlist inside SystemStatusService.
        </x-status-message>
    </section>
@endsection