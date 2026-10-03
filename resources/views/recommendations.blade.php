@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'Recommendations')

@section('content')
    <div class="mx-auto w-full max-w-[1080px]">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
            <div>
                <p class="eyebrow">Next steps</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">What to work on next</h1>
                <p class="mt-2 max-w-[64ch] text-sm leading-6 text-fg-muted">Suggestions based on your current challenge, completed work, and Boss Challenge results.</p>
            </div>
            <a href="{{ route('learning-path') }}" class="btn btn-secondary btn-sm">View Learning Path →</a>
        </header>

        @if ($recommendations->isEmpty())
            <section class="panel mt-6 px-6 py-10 text-center" aria-labelledby="no-recommendations-title">
                <h2 id="no-recommendations-title" class="text-lg font-semibold">NO RECOMMENDATIONS</h2>
                <p class="mt-2 text-sm text-fg-muted">Your next steps will appear here as your learning record grows.</p>
            </section>
        @else
            <section class="mt-6" aria-labelledby="next-actions-title">
                <div class="mb-3">
                    <p class="eyebrow">Your learning path</p>
                    <h2 id="next-actions-title" class="mt-1 text-lg font-semibold">Next actions</h2>
                </div>
                <div class="panel px-5 py-1 md:px-6">
                    <x-recommendation-cards :recommendations="$recommendations" variant="student" />
                </div>
            </section>
        @endif
    </div>
@endsection
