@extends('layouts.app', ['role' => $role])

@section('title', $course->name.' Missions')

@section('content')
    <div class="admin-management">
    <nav class="admin-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('admin.courses') }}">Courses</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('admin.courses.edit', $course) }}">{{ $course->name }}</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Missions</span>
    </nav>

    <x-page-header title="Mission Management" subtitle="{{ $course->name }} · Manage challenge content and learning order.">
        <x-slot:actions>
            <x-badge tone="cyan">ORDERED BY ORDER_NUM</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <x-panel title="Missions in {{ $course->name }}">
        <x-slot:status>
            <x-badge tone="dim">{{ $missions->count() }} {{ Str::plural('mission', $missions->count()) }}</x-badge>
        </x-slot:status>

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
                            <th scope="col">Actions</th>
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

                                <td data-label="Actions">
                                    <a href="{{ route('admin.courses.missions.edit', [$course, $mission]) }}" class="admin-row-link">
                                        EDIT →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>
    </div>
@endsection
