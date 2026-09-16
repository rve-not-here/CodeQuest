@extends('layouts.app', ['role' => 'student'])

@section('title', 'Design System')

@section('content')
    <x-page-header
        :title="'Design System'"
        subtitle="Application shell v1 · System 404 visual identity"
        icon="⌂"
    >
        <x-slot:actions>
            <x-badge tone="phosphor">SHELL.ON</x-badge>
            <x-badge tone="amber">PHASE 1</x-badge>
        </x-slot:actions>
    </x-page-header>

    <section class="space-y-3 mb-8">
        <x-panel title="Status Messages">
            <div class="space-y-2">
                <x-status-message type="success" title="SIGNAL RESTORED">Mission verified. +50 XP credited to operator.</x-status-message>
                <x-status-message type="info" title="SYSTEM INFO">Assessment window opens when path reaches 100%.</x-status-message>
                <x-status-message type="warning" title="WARN">Next mission requires 3 of 5 modules restored.</x-status-message>
                <x-status-message type="error" title="ERR">MISSION NOT RESTORED — validation rule failed.</x-status-message>
            </div>
        </x-panel>
    </section>

    <section class="mb-8">
        <x-panel title="Progress Indicators">
            <div class="space-y-4 max-w-md">
                <x-progress-bar label="Learning Path" :current="4" :total="10" />
                <x-progress-bar label="Current Module" :current="1" :total="5" tone="amber" />
                <x-progress-bar label="Assessment Readiness" :current="70" :total="100" tone="cyan" />
            </div>
        </x-panel>
    </section>

    <section class="mb-8">
        <x-panel title="Actions & Status">
            <div class="flex flex-wrap items-center gap-4">
                <button type="button" class="btn-primary">CONTINUE LEARNING →</button>
                <button type="button" class="btn-ghost">HINT</button>
                <x-badge tone="dim">▸ LOCKED</x-badge>
                <x-badge tone="alert">MISSION NOT RESTORED</x-badge>
                <x-badge tone="cyan">+50 XP</x-badge>
            </div>
        </x-panel>
    </section>
@endsection
