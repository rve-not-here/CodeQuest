@extends('layouts.app', ['role' => $role])

@section('title', $course->name.' Missions')

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>Mission Management</h1><p>{{ $course->name }} · Manage challenge content and learning order.</p></div>
        <div class="admin-actions"><a href="{{ route('admin.courses') }}" class="btn-ghost">◀ BACK TO COURSES</a>
            <x-badge tone="cyan">ORDERED BY ORDER_NUM</x-badge></div>
    </header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <h2 class="text-sm font-bold text-accent mb-2">Missions in {{ $course->name }}</h2>

    <p class="result-count mb-3 text-sm text-ink">{{ $missions->count() }} {{ Str::plural('mission', $missions->count()) }}</p>

    @if ($missions->isEmpty())
        <x-status-message type="info" title="NO MISSIONS">
            No missions are defined for this course.
        </x-status-message>
    @else
        <div class="admin-table-region">
            <table class="table-stack admin-table" role="table">
                <caption>Course missions</caption>
                <thead>
                    <tr>
                        <th scope="col">Order</th>
                        <th scope="col">Mission</th>
                        <th scope="col">Difficulty</th>
                        <th scope="col">Points</th>
                        <th scope="col">Section</th>
                        <th scope="col">Edit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($missions as $mission)
                        <tr>
                            <td data-label="Order">
                                {{ $mission->order_num }}
                            </td>

                            <th scope="row" data-label="Mission">
                                <a href="{{ route('admin.courses.missions.edit', [$course, $mission]) }}" class="text-accent hover:underline">
                                    {{ $mission->title }}
                                </a>
                            </th>

                            <td data-label="Difficulty">
                                <x-badge tone="{{ match ($mission->difficulty) {
                                    'HARD' => 'amber',
                                    'MEDIUM' => 'cyan',
                                    default => 'dim',
                                } }}">{{ $mission->difficulty }}</x-badge>
                            </td>

                            <td data-label="Points">
                                {{ $mission->points }}
                            </td>

                            <td data-label="Section">
                                {{ $mission->section?->title ?? '—' }}
                            </td>

                            <td data-label="Edit">
                                <a href="{{ route('admin.courses.missions.edit', [$course, $mission]) }}" class="text-sm font-bold text-info hover:underline">
                                    EDIT →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    </div>
@endsection
