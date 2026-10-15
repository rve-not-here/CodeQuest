@extends('layouts.app', ['role' => $role])

@section('title', $course->name.' Sections')

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>Section Management</h1><p>{{ $course->name }} · Manage sections in learning order.</p></div>
        <div class="admin-actions"><a href="{{ route('admin.courses') }}" class="btn-ghost">◀ BACK TO COURSES</a>
            <x-badge tone="cyan">ORDERED BY ORDER_NUM</x-badge></div>
    </header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <h2 class="text-sm font-bold text-accent mb-2">Sections in {{ $course->name }}</h2>

    <p class="result-count mb-3 text-sm text-ink">{{ $sections->count() }} {{ Str::plural('section', $sections->count()) }}</p>

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
                        <th scope="col">Edit</th>
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

                            <td data-label="Edit">
                                <a href="{{ route('admin.courses.sections.edit', [$course, $section]) }}" class="text-sm font-bold text-info hover:underline">
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
