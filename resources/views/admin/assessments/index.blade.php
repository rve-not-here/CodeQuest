@extends('layouts.app', ['role' => $role])

@section('title', $course->name.' Boss Challenge')

@section('content')
    <x-page-header
        title="Boss Challenge Management"
        subtitle="{{ $course->name }} — the single Boss Challenge for this course. assessment.status is an access gate: locking or drafting seals the challenge but never rewrites recorded attempts. Grading rules are view-only in this story."
    >
        <x-slot:actions>
            <a href="{{ route('admin.courses') }}" class="btn-ghost">◀ BACK TO COURSES</a>
            @if ($assessment !== null)
                <x-badge tone="{{ $assessment->status === 'active' ? 'cyan' : 'amber' }}">{{ strtoupper($assessment->status) }}</x-badge>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <h2 class="text-sm font-bold text-phosphor mb-2">Boss Challenge of {{ $course->name }}</h2>

    @if ($assessment === null)
        <x-status-message type="info" title="NO BOSS CHALLENGE">
            No Boss Challenge is defined for this course yet. Grading authors shall author it through the AssessmentSeeder before it appears here.
        </x-status-message>
    @else
        <div class="panel p-0 overflow-x-auto">
            <table class="table-stack w-full text-left">
                <thead>
                    <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                        <th class="px-4 py-3">Challenge</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Passing Score</th>
                        <th class="px-4 py-3">Edit</th>
                    </tr>
                </thead>
                <tbody class="font-body text-lg">
                    <tr class="border-b border-phosphor-dim/40 align-top">
                        <td class="px-4 py-3" data-label="Challenge">
                            <a href="{{ route('admin.courses.assessment.edit', [$course, $assessment]) }}" class="text-phosphor hover:underline">
                                {{ $assessment->title }}
                            </a>
                            <span class="block text-[15px] text-phosphor-dim">{{ $assessment->description }}</span>
                        </td>

                        <td class="px-4 py-3" data-label="Status">
                            <x-badge tone="{{ $assessment->status === 'active' ? 'cyan' : ($assessment->status === 'locked' ? 'amber' : 'dim') }}">{{ strtoupper($assessment->status) }}</x-badge>
                        </td>

                        <td class="px-4 py-3 font-display text-[10px] text-phosphor-dim" data-label="Passing Score">
                            {{ $assessment->passing_score }}%
                        </td>

                        <td class="px-4 py-3" data-label="Edit">
                            <a href="{{ route('admin.courses.assessment.edit', [$course, $assessment]) }}" class="text-sm font-bold text-cyan hover:underline">
                                EDIT →
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
@endsection