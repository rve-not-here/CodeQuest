@extends('layouts.app', ['role' => $role])

@section('title', 'Recommendations')

@section('content')
    <x-page-header
        title="Recommendations"
        subtitle="Deterministic next actions derived from your own progress. No fortune-telling."
        icon="✴"
    />

    @if ($recommendations->isEmpty())
        <x-status-message type="info" title="NO RECOMMENDATIONS">
            No recommendations right now. Complete missions and Boss Challenges to generate suggestions.
        </x-status-message>
    @endif

    <x-panel title="NEXT ACTIONS">
        <x-recommendation-cards :recommendations="$recommendations" />
    </x-panel>
@endsection