@extends('layouts.app', ['role' => 'student'])

@section('title', 'Design System')

@section('content')
    <div class="guest-catalogue">
        <x-page-header
            :title="'Design System'"
            subtitle="Component examples only. Values do not represent student records or eligibility."
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
                    <x-status-message type="success" title="SIGNAL RESTORED">Example success message.</x-status-message>
                    <x-status-message type="info" title="SYSTEM INFO">Example information message.</x-status-message>
                    <x-status-message type="warning" title="WARN">Example warning message.</x-status-message>
                    <x-status-message type="error" title="ERR">MISSION NOT RESTORED — validation rule failed.</x-status-message>
                </div>
            </x-panel>
        </section>

        <section class="mb-8">
            <x-panel title="Progress Indicators">
                <div class="space-y-4 max-w-md">
                    <x-progress-bar label="Example course progress" :current="4" :total="10" />
                    <x-progress-bar label="Example section progress" :current="1" :total="5" tone="amber" />
                    <x-progress-bar label="Example progress indicator" :current="70" :total="100" tone="cyan" />
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
    </div>
@endsection
