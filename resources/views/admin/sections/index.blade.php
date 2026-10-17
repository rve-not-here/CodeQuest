@extends('layouts.app', ['role' => $role])

@section('title', $course->name.' Sections')

@section('content')
    <div class="admin-management">
    <nav class="admin-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('admin.courses') }}">Courses</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('admin.courses.edit', $course) }}">{{ $course->name }}</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Sections</span>
    </nav>

    <x-page-header title="Section Management" subtitle="{{ $course->name }} · Manage sections in learning order.">
        <x-slot:actions>
            <x-badge tone="cyan">ORDERED BY ORDER_NUM</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <x-panel title="Sections in {{ $course->name }}">
        <x-slot:status>
            <x-badge tone="dim">{{ $sections->count() }} {{ Str::plural('section', $sections->count()) }}</x-badge>
        </x-slot:status>

        @if ($sections->isEmpty())
            <x-status-message type="info" title="NO SECTIONS">
                No sections are defined for this course.
            </x-status-message>
        @else
            <div class="admin-table-region">
                <table class="table-stack admin-table" role="table">
                    <caption>Course sections</caption>
                    <thead>
                        <tr>
                            <th scope="col">Order</th>
                            <th scope="col">Section</th>
                            <th scope="col">Description</th>
                            <th scope="col">Missions</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sections as $section)
                            <tr>
                                <td data-label="Order">
                                    {{ $section->order_num }}
                                </td>

                                <th scope="row" data-label="Section">
                                    <a href="{{ route('admin.courses.sections.edit', [$course, $section]) }}" class="text-accent hover:underline">
                                        {{ $section->title }}
                                    </a>
                                </th>

                                <td data-label="Description">
                                    {{ $section->description }}
                                </td>

                                <td data-label="Missions">
                                    {{ $section->missions_count }}
                                </td>

                                <td data-label="Actions">
                                    <a href="{{ route('admin.courses.sections.edit', [$course, $section]) }}" class="admin-row-link">
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
