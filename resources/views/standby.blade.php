@extends('layouts.app', ['role' => $role ?? 'student'])

@section('title', 'Module: ' . ucfirst($slug))

@section('content')
    <x-page-header
        :title="'Module: ' . ucfirst($slug)"
        subtitle="Shell ready. Feature module staged for a later phase."
        icon="▣"
    >
        <x-slot:actions>
            <x-badge tone="amber">PENDING</x-badge>
        </x-slot:actions>
    </x-page-header>

    <x-status-message type="info" title="Standby">
        This module is part of the Phase 1 application shell. Personnel awaiting
        implementation in a later phase.
    </x-status-message>
@endsection
