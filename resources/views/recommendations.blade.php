@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Recommendations')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Recommendations</span>
            <a href="{{ route('learning-path') }}">Learning path →</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Next actions</span>
            <h1 class="atlas-records-title">What to work on next</h1>
            <p class="atlas-records-lede">Suggestions based on your current challenge, completed work, and Boss Challenge results.</p>
        </header>

        @if ($recommendations->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="no-recommendations-title">
                <h2 id="no-recommendations-title" class="atlas-records-empty-title">No recommendations yet</h2>
                <p class="atlas-records-empty-copy">Your next steps appear here as your learning record grows.</p>
            </section>
        @else
            <x-recommendation-cards :recommendations="$recommendations" variant="evidence" />
        @endif
    </div>
@endsection
