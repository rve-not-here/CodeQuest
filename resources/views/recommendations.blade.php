@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Recommendations')

@section('content')
    <div class="atlas-records">
        <header class="atlas-records-heading">
            <h1 class="atlas-records-title">What to work on next</h1>
            <p class="atlas-records-lede">Suggestions based on your current challenge, completed work, and Boss Challenge results.</p>
            <p class="atlas-record-group-note">Ordered by recorded performance and incomplete requirements. A locked action names what opens it; recommendations never bypass progression.</p>
        </header>

        @if ($recommendations->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="no-recommendations-title">
                <h2 id="no-recommendations-title" class="atlas-records-empty-title">No recommendations yet</h2>
                <p class="atlas-records-empty-copy">Your next steps appear here as your learning record grows.</p>
                <a href="{{ route('learning-path') }}" class="btn btn-secondary">Open learning path <span aria-hidden="true">→</span></a>
            </section>
        @else
            <x-recommendation-cards :recommendations="$recommendations" />
        @endif
    </div>
@endsection
