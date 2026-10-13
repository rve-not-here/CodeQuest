@extends('layouts.app', ['role' => $role])

@section('title', $course->name.' Boss Challenge')

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>Boss Challenge Management</h1><p>{{ $course->name }} — the single Boss Challenge for this course. Locking or drafting seals the challenge without rewriting recorded attempts. Grading rules are view-only.</p></div>
        <div class="admin-actions"><a href="{{ route('admin.courses') }}" class="btn-ghost">◀ BACK TO COURSES</a>
            @if ($assessment !== null)
                <x-badge tone="{{ $assessment->status === 'active' ? 'cyan' : 'amber' }}">{{ strtoupper($assessment->status) }}</x-badge>
            @endif</div>
    </header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <h2 class="text-sm font-bold text-phosphor mb-2">Boss Challenge of {{ $course->name }}</h2>

    @if ($assessment === null)
        <x-status-message type="info" title="NO BOSS CHALLENGE">
            No Boss Challenge is defined for this course.
        </x-status-message>
    @else
        <div class="admin-table-region">
            <table class="table-stack admin-table" role="table">
                <caption>Course Boss Challenge</caption>
                <thead>
                    <tr>
                        <th scope="col">Challenge</th>
                        <th scope="col">Status</th>
                        <th scope="col">Passing Score</th>
                        <th scope="col">Edit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row" data-label="Challenge">
                            <a href="{{ route('admin.courses.assessment.edit', [$course, $assessment]) }}" class="text-phosphor hover:underline">
                                {{ $assessment->title }}
                            </a>
                            <span class="block text-[15px] text-phosphor-dim">{{ $assessment->description }}</span>
                        </th>

                        <td data-label="Status">
                            <x-badge tone="{{ $assessment->status === 'active' ? 'cyan' : ($assessment->status === 'locked' ? 'amber' : 'dim') }}">{{ strtoupper($assessment->status) }}</x-badge>
                        </td>

                        <td data-label="Passing Score">
                            {{ $assessment->passing_score }}%
                        </td>

                        <td data-label="Edit">
                            <a href="{{ route('admin.courses.assessment.edit', [$course, $assessment]) }}" class="text-sm font-bold text-cyan hover:underline">
                                EDIT →
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
    </div>
@endsection
